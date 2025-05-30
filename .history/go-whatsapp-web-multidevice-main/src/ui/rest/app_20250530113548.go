package rest

import (
	"fmt"
	"time"

	domainApp "github.com/aldinokemal/go-whatsapp-web-multidevice/domains/app"
	"github.com/aldinokemal/go-whatsapp-web-multidevice/pkg/utils"
	"github.com/gofiber/fiber/v2"
)

type App struct {
	Service domainApp.IAppUsecase
}

func InitRestApp(app *fiber.App, service domainApp.IAppUsecase) App {
	rest := App{Service: service}
	app.Get("/app/login", rest.Login)
	app.Get("/app/login-fresh", rest.LoginFresh)
	app.Get("/app/login-with-code", rest.LoginWithCode)
	app.Get("/app/logout", rest.Logout)
	app.Get("/app/reconnect", rest.Reconnect)
	app.Get("/app/devices", rest.Devices)

	return App{Service: service}
}

func (handler *App) Login(c *fiber.Ctx) error {
	response, err := handler.Service.Login(c.UserContext())
	utils.PanicIfNeeded(err)

	return c.JSON(utils.ResponseData{
		Status:  200,
		Code:    "SUCCESS",
		Message: "Login success",
		Results: map[string]any{
			"qr_link":     fmt.Sprintf("%s://%s/%s", c.Protocol(), c.Hostname(), response.ImagePath),
			"qr_duration": response.Duration,
		},
	})
}

func (handler *App) LoginFresh(c *fiber.Ctx) error {
	startTime := time.Now()
	response, err := handler.Service.LoginFresh(c.UserContext())
	utils.PanicIfNeeded(err)

	return c.JSON(utils.ResponseData{
		Status:  200,
		Code:    "SUCCESS",
		Message: "Fresh login success - new QR code generated",
		Results: map[string]any{
			"qr_link":     fmt.Sprintf("%s://%s/%s", c.Protocol(), c.Hostname(), response.ImagePath),
			"qr_duration": response.Duration,
			"fresh":       true,
			"generated_at": startTime.Format("2006-01-02 15:04:05.000"),
			"expires_at":   startTime.Add(time.Duration(response.Duration) * time.Second).Format("2006-01-02 15:04:05.000"),
			"total_time_ms": time.Since(startTime).Milliseconds(),
		},
	})
}

func (handler *App) LoginWithCode(c *fiber.Ctx) error {
	pairCode, err := handler.Service.LoginWithCode(c.UserContext(), c.Query("phone"))
	utils.PanicIfNeeded(err)

	return c.JSON(utils.ResponseData{
		Status:  200,
		Code:    "SUCCESS",
		Message: "Login with code success",
		Results: map[string]any{
			"pair_code": pairCode,
		},
	})
}

func (handler *App) Logout(c *fiber.Ctx) error {
	err := handler.Service.Logout(c.UserContext())
	utils.PanicIfNeeded(err)

	return c.JSON(utils.ResponseData{
		Status:  200,
		Code:    "SUCCESS",
		Message: "Success logout",
		Results: nil,
	})
}

func (handler *App) Reconnect(c *fiber.Ctx) error {
	err := handler.Service.Reconnect(c.UserContext())
	utils.PanicIfNeeded(err)

	return c.JSON(utils.ResponseData{
		Status:  200,
		Code:    "SUCCESS",
		Message: "Reconnect success",
		Results: nil,
	})
}

func (handler *App) Devices(c *fiber.Ctx) error {
	devices, err := handler.Service.FetchDevices(c.UserContext())
	utils.PanicIfNeeded(err)

	return c.JSON(utils.ResponseData{
		Status:  200,
		Code:    "SUCCESS",
		Message: "Fetch device success",
		Results: devices,
	})
}
