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

// LoginFresh forces a fresh QR code generation using smart session management
func (service serviceApp) LoginFresh(ctx context.Context) (response domainApp.LoginResponse, err error) {
	startTime := time.Now()
	requestID := fiberUtils.UUIDv4()[:8] // Short ID for tracking

	if service.WaCli == nil {
		return response, pkgError.ErrWaCLI
	}

	logrus.WithFields(logrus.Fields{
		"request_id": requestID,
		"timestamp": startTime.Format("2006-01-02 15:04:05.000"),
	}).Info("🚀 Starting smart fresh login process...")

	// Smart approach: Disconnect without destroying session store
	disconnectStart := time.Now()
	service.WaCli.Disconnect()
	logrus.WithFields(logrus.Fields{
		"request_id": requestID,
		"duration_ms": time.Since(disconnectStart).Milliseconds(),
	}).Info("📡 WhatsApp client disconnected (smart mode)")

	// Clear any existing QR code files (non-blocking)
	cleanupStart := time.Now()
	go func() {
		qrFiles, _ := filepath.Glob(filepath.Join(config.PathQrCode, "scan-qr*.png"))
		logrus.WithFields(logrus.Fields{
			"request_id": requestID,
			"files_found": len(qrFiles),
		}).Info("🧹 Cleaning up old QR files")
		for _, file := range qrFiles {
			_ = os.Remove(file)
		}
		logrus.WithFields(logrus.Fields{
			"request_id": requestID,
			"duration_ms": time.Since(cleanupStart).Milliseconds(),
		}).Info("✅ QR files cleanup completed")
	}()

	logrus.WithFields(logrus.Fields{
		"request_id": requestID,
		"total_prep_ms": time.Since(startTime).Milliseconds(),
	}).Info("⏱️ Smart preparation phase completed")

	chImage := make(chan string, 1) // Buffered channel to prevent blocking
	chError := make(chan error, 1)  // Error channel with timeout

	// Smart QR channel handling - similar to working regular login
	qrChannelStart := time.Now()
	logrus.WithFields(logrus.Fields{
		"request_id": requestID,
		"timestamp": time.Now().Format("2006-01-02 15:04:05.000"),
	}).Info("🔄 Getting smart fresh QR channel...")

	ch, err := service.WaCli.GetQRChannel(context.Background())
	if err != nil {
		logrus.WithFields(logrus.Fields{
			"request_id": requestID,
			"error": err.Error(),
			"duration_ms": time.Since(qrChannelStart).Milliseconds(),
		}).Error("❌ Error getting fresh QR channel")

		// Smart handling like regular login - DON'T destroy session
		if errors.Is(err, whatsmeow.ErrQRStoreContainsID) {
			logrus.WithFields(logrus.Fields{
				"request_id": requestID,
			}).Info("🔧 Session exists - connecting to websocket...")

			// Just connect to websocket like regular login
			_ = service.WaCli.Connect()
			if service.WaCli.IsLoggedIn() {
				logrus.WithFields(logrus.Fields{
					"request_id": requestID,
				}).Info("✅ Already logged in - returning session saved")
				return response, pkgError.ErrAlreadyLoggedIn
			}

			logrus.WithFields(logrus.Fields{
				"request_id": requestID,
			}).Info("⚠️ Session saved but not logged in - need fresh QR")
			return response, pkgError.ErrSessionSaved
		} else {
			return response, pkgError.ErrQrChannel
		}
	}

	logrus.WithFields(logrus.Fields{
		"request_id": requestID,
		"duration_ms": time.Since(qrChannelStart).Milliseconds(),
	}).Info("✅ Smart QR channel obtained successfully")

	// QR code generation goroutine with timeout
	go func() {
		qrGenStart := time.Now()
		defer func() {
			if r := recover(); r != nil {
				logrus.WithFields(logrus.Fields{
					"request_id": requestID,
					"panic": r,
					"duration_ms": time.Since(qrGenStart).Milliseconds(),
				}).Error("💥 Panic in QR generation")
				chError <- fmt.Errorf("QR generation panic: %v", r)
			}
		}()

		logrus.WithFields(logrus.Fields{
			"request_id": requestID,
			"timestamp": time.Now().Format("2006-01-02 15:04:05.000"),
		}).Info("🎯 Starting QR code generation goroutine...")

		for evt := range ch {
			eventTime := time.Now()
			response.Code = evt.Code
			response.Duration = evt.Timeout / time.Second / 2

			logrus.WithFields(logrus.Fields{
				"request_id": requestID,
				"event_type": evt.Event,
				"qr_duration": response.Duration,
				"timestamp": eventTime.Format("2006-01-02 15:04:05.000"),
			}).Info("📨 Received QR event")

			if evt.Event == "code" {
				qrWriteStart := time.Now()
				qrUUID := fiberUtils.UUIDv4()
				qrPath := fmt.Sprintf("%s/scan-qr-fresh-%s.png", config.PathQrCode, qrUUID)

				logrus.WithFields(logrus.Fields{
					"request_id": requestID,
					"qr_uuid": qrUUID,
					"qr_path": qrPath,
					"timestamp": qrWriteStart.Format("2006-01-02 15:04:05.000"),
				}).Info("📝 Writing QR code to file...")

				err := qrcode.WriteFile(evt.Code, qrcode.Medium, 512, qrPath)
				if err != nil {
					logrus.WithFields(logrus.Fields{
						"request_id": requestID,
						"qr_uuid": qrUUID,
						"error": err.Error(),
						"duration_ms": time.Since(qrWriteStart).Milliseconds(),
					}).Error("❌ Error writing QR code to file")
					chError <- err
					return
				}

				logrus.WithFields(logrus.Fields{
					"request_id": requestID,
					"qr_uuid": qrUUID,
					"qr_path": qrPath,
					"write_duration_ms": time.Since(qrWriteStart).Milliseconds(),
					"total_generation_ms": time.Since(qrGenStart).Milliseconds(),
					"timestamp": time.Now().Format("2006-01-02 15:04:05.000"),
				}).Info("✅ Fresh QR code generated successfully")

				// Auto-cleanup QR file after duration
				go func(path string, duration time.Duration, uuid string) {
					logrus.WithFields(logrus.Fields{
						"request_id": requestID,
						"qr_uuid": uuid,
						"cleanup_after_seconds": duration.Seconds(),
					}).Info("⏰ Scheduled QR file cleanup")

					time.Sleep(duration)
					err := os.Remove(path)
					if err != nil {
						logrus.WithFields(logrus.Fields{
							"request_id": requestID,
							"qr_uuid": uuid,
							"error": err.Error(),
						}).Warn("⚠️ Failed to cleanup QR file")
					} else {
						logrus.WithFields(logrus.Fields{
							"request_id": requestID,
							"qr_uuid": uuid,
						}).Info("🗑️ QR file cleaned up successfully")
					}
				}(qrPath, time.Duration(response.Duration)*time.Second, qrUUID)

				chImage <- qrPath
				return
			} else {
				logrus.WithFields(logrus.Fields{
					"request_id": requestID,
					"event_type": evt.Event,
					"timestamp": eventTime.Format("2006-01-02 15:04:05.000"),
				}).Error("❌ Error event in fresh QR generation")
			}
		}

		logrus.WithFields(logrus.Fields{
			"request_id": requestID,
			"duration_ms": time.Since(qrGenStart).Milliseconds(),
		}).Error("❌ QR channel closed without generating code")
		chError <- fmt.Errorf("QR channel closed without generating code")
	}()

	// Connect with timeout
	connectStart := time.Now()
	logrus.WithFields(logrus.Fields{
		"request_id": requestID,
		"timestamp": connectStart.Format("2006-01-02 15:04:05.000"),
	}).Info("🔌 Connecting to WhatsApp...")

	err = service.WaCli.Connect()
	if err != nil {
		logrus.WithFields(logrus.Fields{
			"request_id": requestID,
			"error": err.Error(),
			"duration_ms": time.Since(connectStart).Milliseconds(),
			"total_duration_ms": time.Since(startTime).Milliseconds(),
		}).Error("❌ Error connecting to WhatsApp for fresh login")
		return response, pkgError.ErrReconnect
	}

	logrus.WithFields(logrus.Fields{
		"request_id": requestID,
		"duration_ms": time.Since(connectStart).Milliseconds(),
	}).Info("✅ Connected to WhatsApp successfully")

	// Wait for QR code or timeout
	waitStart := time.Now()
	logrus.WithFields(logrus.Fields{
		"request_id": requestID,
		"timeout_seconds": 15,
		"timestamp": waitStart.Format("2006-01-02 15:04:05.000"),
	}).Info("⏳ Waiting for QR code generation...")

	select {
	case imagePath := <-chImage:
		response.ImagePath = imagePath
		totalDuration := time.Since(startTime)
		logrus.WithFields(logrus.Fields{
			"request_id": requestID,
			"qr_path": imagePath,
			"wait_duration_ms": time.Since(waitStart).Milliseconds(),
			"total_duration_ms": totalDuration.Milliseconds(),
			"timestamp": time.Now().Format("2006-01-02 15:04:05.000"),
		}).Info("🎉 Fresh login QR code ready!")
		return response, nil

	case err := <-chError:
		totalDuration := time.Since(startTime)
		logrus.WithFields(logrus.Fields{
			"request_id": requestID,
			"error": err.Error(),
			"wait_duration_ms": time.Since(waitStart).Milliseconds(),
			"total_duration_ms": totalDuration.Milliseconds(),
		}).Error("❌ Fresh login failed")
		return response, pkgError.ErrQrChannel

	case <-time.After(15 * time.Second):
		totalDuration := time.Since(startTime)
		logrus.WithFields(logrus.Fields{
			"request_id": requestID,
			"timeout_seconds": 15,
			"total_duration_ms": totalDuration.Milliseconds(),
		}).Error("⏰ Fresh login timeout after 15 seconds")
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
