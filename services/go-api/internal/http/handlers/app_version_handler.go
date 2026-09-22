package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

type AppVersionConfig struct {
	LatestVersion string
	LatestBuild   int
	MinimumBuild  int
	ForceUpdate   bool
	PlayStoreURL  string
	UpdateMessage string
}

type AppVersionHandler struct {
	config AppVersionConfig
}

func NewAppVersionHandler(config AppVersionConfig) *AppVersionHandler {
	return &AppVersionHandler{config: config}
}

func (h *AppVersionHandler) Get(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "OK",
		"data": gin.H{
			"latest_version": h.config.LatestVersion,
			"latest_build":   h.config.LatestBuild,
			"minimum_build":  h.config.MinimumBuild,
			"force_update":   h.config.ForceUpdate,
			"play_store_url": h.config.PlayStoreURL,
			"update_message": h.config.UpdateMessage,
		},
	})
}
