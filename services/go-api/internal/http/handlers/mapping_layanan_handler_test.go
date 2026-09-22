package handlers

import (
	"context"
	"encoding/json"
	"errors"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/models"
	"github.com/ulab/ulab-go-api/internal/repo"
)

type mappingLayananRepoStub struct {
	items []models.MappingLayananItem
	err   error
}

func (s mappingLayananRepoStub) ListByScope(
	context.Context,
	repo.ListMappingLayananParams,
) ([]models.MappingLayananItem, error) {
	return s.items, s.err
}

func TestMappingLayananRejectsInvalidQuery(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tests := []struct {
		name string
		url  string
	}{
		{name: "scope kosong", url: "/api/service/mapping-layanan"},
		{name: "scope tidak dikenal", url: "/api/service/mapping-layanan?scope=lainnya"},
		{name: "kategori tidak valid", url: "/api/service/mapping-layanan?scope=suhu&kategori=invalid"},
		{
			name: "pencarian terlalu panjang",
			url:  "/api/service/mapping-layanan?scope=suhu&search=" + strings.Repeat("a", 101),
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			router := gin.New()
			handler := NewMappingLayananHandler(nil)
			router.GET("/api/service/mapping-layanan", handler.GetMappingLayanan)

			recorder := httptest.NewRecorder()
			request := httptest.NewRequest(http.MethodGet, tt.url, nil)
			router.ServeHTTP(recorder, request)

			if recorder.Code != http.StatusBadRequest {
				t.Fatalf("status = %d, ingin %d", recorder.Code, http.StatusBadRequest)
			}

			var body struct {
				Status int `json:"status"`
			}
			if err := json.Unmarshal(recorder.Body.Bytes(), &body); err != nil {
				t.Fatalf("response bukan JSON valid: %v", err)
			}
			if body.Status != http.StatusBadRequest {
				t.Fatalf("body status = %d, ingin %d", body.Status, http.StatusBadRequest)
			}
		})
	}
}

func TestMappingLayananResponseContract(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repoStub := mappingLayananRepoStub{items: []models.MappingLayananItem{
		{ID: 1, Kategori: "KAN", NamaLayanan: "Alat A", Lingkup: "Suhu"},
		{ID: 2, Kategori: "Non KAN", NamaLayanan: "Alat B", Lingkup: "Suhu & Kelembaban"},
	}}
	router := gin.New()
	router.GET(
		"/api/service/mapping-layanan",
		NewMappingLayananHandler(repoStub).GetMappingLayanan,
	)

	recorder := httptest.NewRecorder()
	request := httptest.NewRequest(
		http.MethodGet,
		"/api/service/mapping-layanan?scope=suhu",
		nil,
	)
	router.ServeHTTP(recorder, request)

	if recorder.Code != http.StatusOK {
		t.Fatalf("status = %d, ingin %d", recorder.Code, http.StatusOK)
	}

	var body struct {
		Status int `json:"status"`
		Data   struct {
			Summary struct {
				Total  int `json:"total"`
				KAN    int `json:"kan"`
				NonKAN int `json:"non_kan"`
			} `json:"summary"`
			Items []models.MappingLayananItem `json:"items"`
		} `json:"data"`
	}
	if err := json.Unmarshal(recorder.Body.Bytes(), &body); err != nil {
		t.Fatalf("response bukan JSON valid: %v", err)
	}
	if body.Status != http.StatusOK || body.Data.Summary.Total != 2 ||
		body.Data.Summary.KAN != 1 || body.Data.Summary.NonKAN != 1 ||
		len(body.Data.Items) != 2 {
		t.Fatalf("kontrak response tidak sesuai: %s", recorder.Body.String())
	}
}

func TestMappingLayananHidesRepositoryError(t *testing.T) {
	gin.SetMode(gin.TestMode)

	router := gin.New()
	router.GET(
		"/api/service/mapping-layanan",
		NewMappingLayananHandler(mappingLayananRepoStub{
			err: errors.New("host rahasia dan kredensial database"),
		}).GetMappingLayanan,
	)

	recorder := httptest.NewRecorder()
	request := httptest.NewRequest(
		http.MethodGet,
		"/api/service/mapping-layanan?scope=kelistrikan",
		nil,
	)
	router.ServeHTTP(recorder, request)

	if recorder.Code != http.StatusServiceUnavailable {
		t.Fatalf("status = %d, ingin %d", recorder.Code, http.StatusServiceUnavailable)
	}
	if strings.Contains(recorder.Body.String(), "rahasia") {
		t.Fatalf("detail error repository bocor: %s", recorder.Body.String())
	}
}
