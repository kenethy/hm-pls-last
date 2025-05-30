package usecase

import (
	"context"
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/aldinokemal/go-whatsapp-web-multidevice/config"
	domainApp "github.com/aldinokemal/go-whatsapp-web-multidevice/domains/app"
	pkgError "github.com/aldinokemal/go-whatsapp-web-multidevice/pkg/error"
	"github.com/aldinokemal/go-whatsapp-web-multidevice/validations"
	fiberUtils "github.com/gofiber/fiber/v2/utils"
	"github.com/sirupsen/logrus"
	"github.com/skip2/go-qrcode"
	"go.mau.fi/libsignal/logger"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/store/sqlstore"
)

type serviceApp struct {
	WaCli *whatsmeow.Client
	db    *sqlstore.Container
}

func NewAppService(waCli *whatsmeow.Client, db *sqlstore.Container) domainApp.IAppUsecase {
	return &serviceApp{
		WaCli: waCli,
		db:    db,
	}
}

func (service serviceApp) Login(_ context.Context) (response domainApp.LoginResponse, err error) {
	if service.WaCli == nil {
		return response, pkgError.ErrWaCLI
	}

	// Disconnect for reconnecting
	service.WaCli.Disconnect()

	chImage := make(chan string)

	ch, err := service.WaCli.GetQRChannel(context.Background())
	if err != nil {
		logrus.Error(err.Error())
		// This error means that we're already logged in, so ignore it.
		if errors.Is(err, whatsmeow.ErrQRStoreContainsID) {
			_ = service.WaCli.Connect() // just connect to websocket
			if service.WaCli.IsLoggedIn() {
				return response, pkgError.ErrAlreadyLoggedIn
			}
			return response, pkgError.ErrSessionSaved
		} else {
			return response, pkgError.ErrQrChannel
		}
	} else {
		go func() {
			for evt := range ch {
				response.Code = evt.Code
				response.Duration = evt.Timeout / time.Second / 2
				if evt.Event == "code" {
					qrPath := fmt.Sprintf("%s/scan-qr-%s.png", config.PathQrCode, fiberUtils.UUIDv4())
					err = qrcode.WriteFile(evt.Code, qrcode.Medium, 512, qrPath)
					if err != nil {
						logrus.Error("Error when write qr code to file: ", err)
					}
					go func() {
						time.Sleep(response.Duration * time.Second)
						err := os.Remove(qrPath)
						if err != nil {
							logrus.Error("error when remove qrImage file", err.Error())
						}
					}()
					chImage <- qrPath
				} else {
					logrus.Error("error when get qrCode", evt.Event)
				}
			}
		}()
	}

	err = service.WaCli.Connect()
	if err != nil {
		logger.Error("Error when connect to whatsapp", err)
		return response, pkgError.ErrReconnect
	}
	response.ImagePath = <-chImage

	return response, nil
}

// LoginFresh forces a fresh QR code generation by clearing existing session
func (service serviceApp) LoginFresh(ctx context.Context) (response domainApp.LoginResponse, err error) {
	if service.WaCli == nil {
		return response, pkgError.ErrWaCLI
	}

	logrus.Info("Starting fresh login process...")

	// Simple approach: Disconnect and clear files without full logout
	service.WaCli.Disconnect()

	// Clear any existing QR code files (non-blocking)
	go func() {
		qrFiles, _ := filepath.Glob(filepath.Join(config.PathQrCode, "scan-qr*.png"))
		for _, file := range qrFiles {
			_ = os.Remove(file)
		}
	}()

	// Small delay to ensure disconnect is complete
	time.Sleep(500 * time.Millisecond)

	chImage := make(chan string, 1) // Buffered channel to prevent blocking
	chError := make(chan error, 1)  // Error channel with timeout

	// Get fresh QR channel with timeout context
	ctxTimeout, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	ch, err := service.WaCli.GetQRChannel(ctxTimeout)
	if err != nil {
		logrus.Error("Error getting fresh QR channel: ", err.Error())
		// If error is about existing session, try to clear it
		if errors.Is(err, whatsmeow.ErrQRStoreContainsID) {
			logrus.Info("Clearing existing session for fresh QR...")
			// Clear the store ID to force fresh QR
			service.WaCli.Store.ID = nil
			// Try again
			ch, err = service.WaCli.GetQRChannel(ctxTimeout)
			if err != nil {
				return response, pkgError.ErrQrChannel
			}
		} else {
			return response, pkgError.ErrQrChannel
		}
	}

	// QR code generation goroutine with timeout
	go func() {
		defer func() {
			if r := recover(); r != nil {
				logrus.Error("Panic in QR generation: ", r)
				chError <- fmt.Errorf("QR generation panic: %v", r)
			}
		}()

		for evt := range ch {
			response.Code = evt.Code
			response.Duration = evt.Timeout / time.Second / 2
			if evt.Event == "code" {
				qrPath := fmt.Sprintf("%s/scan-qr-fresh-%s.png", config.PathQrCode, fiberUtils.UUIDv4())
				err := qrcode.WriteFile(evt.Code, qrcode.Medium, 512, qrPath)
				if err != nil {
					logrus.Error("Error when write fresh qr code to file: ", err)
					chError <- err
					return
				}

				// Auto-cleanup QR file after duration
				go func(path string, duration time.Duration) {
					time.Sleep(duration)
					_ = os.Remove(path)
				}(qrPath, time.Duration(response.Duration)*time.Second)

				chImage <- qrPath
				logrus.Info("Fresh QR code generated successfully: ", qrPath)
				return
			} else {
				logrus.Error("Error event in fresh QR generation: ", evt.Event)
			}
		}
		chError <- fmt.Errorf("QR channel closed without generating code")
	}()

	// Connect with timeout
	err = service.WaCli.Connect()
	if err != nil {
		logrus.Error("Error when connect to whatsapp for fresh login: ", err)
		return response, pkgError.ErrReconnect
	}

	// Wait for QR code or timeout
	select {
	case imagePath := <-chImage:
		response.ImagePath = imagePath
		logrus.Info("Fresh login QR code ready: ", imagePath)
		return response, nil
	case err := <-chError:
		logrus.Error("Fresh login failed: ", err)
		return response, pkgError.ErrQrChannel
	case <-time.After(15 * time.Second):
		logrus.Error("Fresh login timeout after 15 seconds")
		return response, pkgError.ErrQrChannel
	}
}

func (service serviceApp) LoginWithCode(ctx context.Context, phoneNumber string) (loginCode string, err error) {
	if err = validations.ValidateLoginWithCode(ctx, phoneNumber); err != nil {
		logrus.Errorf("Error when validate login with code: %s", err.Error())
		return loginCode, err
	}

	// detect is already logged in
	if service.WaCli.Store.ID != nil {
		logrus.Warn("User is already logged in")
		return loginCode, pkgError.ErrAlreadyLoggedIn
	}

	// reconnect first
	_ = service.Reconnect(ctx)

	loginCode, err = service.WaCli.PairPhone(ctx, phoneNumber, true, whatsmeow.PairClientChrome, "Chrome (Linux)")
	if err != nil {
		logrus.Errorf("Error when pairing phone: %s", err.Error())
		return loginCode, err
	}

	logrus.Infof("Successfully paired phone with code: %s", loginCode)
	return loginCode, nil
}

func (service serviceApp) Logout(ctx context.Context) (err error) {
	// delete history
	files, err := filepath.Glob(fmt.Sprintf("./%s/history-*", config.PathStorages))
	if err != nil {
		return err
	}

	for _, f := range files {
		err = os.Remove(f)
		if err != nil {
			return err
		}
	}
	// delete qr images
	qrImages, err := filepath.Glob(fmt.Sprintf("./%s/scan-*", config.PathQrCode))
	if err != nil {
		return err
	}

	for _, f := range qrImages {
		err = os.Remove(f)
		if err != nil {
			return err
		}
	}

	// delete senditems
	qrItems, err := filepath.Glob(fmt.Sprintf("./%s/*", config.PathSendItems))
	if err != nil {
		return err
	}

	for _, f := range qrItems {
		if !strings.Contains(f, ".gitignore") {
			err = os.Remove(f)
			if err != nil {
				return err
			}
		}
	}

	err = service.WaCli.Logout(ctx)
	return
}

func (service serviceApp) Reconnect(_ context.Context) (err error) {
	service.WaCli.Disconnect()
	return service.WaCli.Connect()
}

func (service serviceApp) FirstDevice(ctx context.Context) (response domainApp.DevicesResponse, err error) {
	if service.WaCli == nil {
		return response, pkgError.ErrWaCLI
	}

	devices, err := service.db.GetFirstDevice(ctx)
	if err != nil {
		return response, err
	}

	response.Device = devices.ID.String()
	if devices.PushName != "" {
		response.Name = devices.PushName
	} else {
		response.Name = devices.BusinessName
	}

	return response, nil
}

func (service serviceApp) FetchDevices(ctx context.Context) (response []domainApp.DevicesResponse, err error) {
	if service.WaCli == nil {
		return response, pkgError.ErrWaCLI
	}

	devices, err := service.db.GetAllDevices(ctx)
	if err != nil {
		return nil, err
	}

	for _, device := range devices {
		var d domainApp.DevicesResponse
		d.Device = device.ID.String()
		if device.PushName != "" {
			d.Name = device.PushName
		} else {
			d.Name = device.BusinessName
		}

		response = append(response, d)
	}

	return response, nil
}
