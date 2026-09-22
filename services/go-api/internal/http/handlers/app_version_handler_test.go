package handlers

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
)

func TestAppVersionHandlerGet(t *testing.T) {
	gin.SetMode(gin.TestMode)
	handler := NewAppVersionHandler(AppVersionConfig{
		LatestVersion: "1.0.8",
		LatestBuild:   9,
		MinimumBuild:  8,
		ForceUpdate:   false,
		PlayStoreURL:  "https://play.google.com/store/apps/details?id=id.ulabumro.mobile",
		UpdateMessage: "Perbarui aplikasi.",
	})

	recorder := httptest.NewRecorder()
	context, _ := gin.CreateTestContext(recorder)
	context.Request = httptest.NewRequest(http.MethodGet, "/api/mobile/app-version", nil)

	handler.Get(context)

	if recorder.Code != http.StatusOK {
		t.Fatalf("expected status 200, got %d", recorder.Code)
	}

	var response struct {
		Status int `json:"status"`
		Data   struct {
			LatestVersion string `json:"latest_version"`
			LatestBuild   int    `json:"latest_build"`
			MinimumBuild  int    `json:"minimum_build"`
			ForceUpdate   bool   `json:"force_update"`
		} `json:"data"`
	}
	if err := json.Unmarshal(recorder.Body.Bytes(), &response); err != nil {
		t.Fatalf("decode response: %v", err)
	}

	if response.Status != 200 || response.Data.LatestVersion != "1.0.8" || response.Data.LatestBuild != 9 {
		t.Fatalf("unexpected response: %+v", response)
	}
	if response.Data.MinimumBuild != 8 || response.Data.ForceUpdate {
		t.Fatalf("unexpected update policy: %+v", response.Data)
	}
}
