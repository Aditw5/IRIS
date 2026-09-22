package imageoptimizer

import (
	"bytes"
	"context"
	"errors"
	"io"
	"mime/multipart"
	"net/http"
	"net/http/httptest"
	"os"
	"path/filepath"
	"runtime"
	"testing"
	"time"
)

var fakeWebP = []byte("RIFF\x04\x00\x00\x00WEBPVP8 ")

func uploadHeader(t *testing.T, name string, content []byte) *multipart.FileHeader {
	t.Helper()
	var body bytes.Buffer
	writer := multipart.NewWriter(&body)
	part, err := writer.CreateFormFile("fileMitra", name)
	if err != nil {
		t.Fatal(err)
	}
	if _, err := part.Write(content); err != nil {
		t.Fatal(err)
	}
	if err := writer.Close(); err != nil {
		t.Fatal(err)
	}
	req := httptest.NewRequest(http.MethodPost, "/", &body)
	req.Header.Set("Content-Type", writer.FormDataContentType())
	if err := req.ParseMultipartForm(1024 * 1024); err != nil {
		t.Fatal(err)
	}
	return req.MultipartForm.File["fileMitra"][0]
}

func TestStoreToolImageWritesMainAndThumbnail(t *testing.T) {
	requests := 0
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		requests++
		if err := r.ParseMultipartForm(1024 * 1024); err != nil {
			t.Error(err)
		}
		if _, _, err := r.FormFile("image"); err != nil {
			t.Error(err)
		}
		w.Header().Set("Content-Type", "image/webp")
		_, _ = w.Write(fakeWebP)
	}))
	defer server.Close()

	dir := t.TempDir()
	client := New(Config{Enabled: true, URL: server.URL, Timeout: time.Second})
	stored, err := client.StoreToolImage(context.Background(), uploadHeader(t, "alat.jpg", []byte("jpeg")), dir, func() (string, error) {
		t.Fatal("fallback tidak boleh dipanggil")
		return "", nil
	})
	if err != nil {
		t.Fatal(err)
	}
	if !stored.Optimized || filepath.Ext(stored.Filename) != ".webp" || len(stored.CreatedFiles) != 2 {
		t.Fatalf("hasil simpan tidak sesuai: %#v", stored)
	}
	if requests != 2 {
		t.Fatalf("jumlah request = %d, ingin 2", requests)
	}
	for _, name := range stored.CreatedFiles {
		info, err := os.Stat(filepath.Join(dir, name))
		if err != nil {
			t.Fatal(err)
		}
		if runtime.GOOS != "windows" && info.Mode().Perm() != 0o644 {
			t.Fatalf("permission %s = %o, ingin 644", name, info.Mode().Perm())
		}
	}
}

func TestStoreToolImageFallsBackWhenServiceFails(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusServiceUnavailable)
		_, _ = io.WriteString(w, "down")
	}))
	defer server.Close()

	dir := t.TempDir()
	client := New(Config{Enabled: true, URL: server.URL, Timeout: time.Second})
	stored, err := client.StoreToolImage(context.Background(), uploadHeader(t, "alat.png", []byte("png")), dir, func() (string, error) {
		name := "legacy.png"
		return name, os.WriteFile(filepath.Join(dir, name), []byte("png"), 0o644)
	})
	if err != nil {
		t.Fatal(err)
	}
	if stored.Optimized || stored.Filename != "legacy.png" || len(stored.CreatedFiles) != 1 {
		t.Fatalf("fallback tidak sesuai: %#v", stored)
	}
}

func TestStoreToolImageRejectsInvalidImageWithoutFallback(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusUnprocessableEntity)
	}))
	defer server.Close()

	fallbackCalled := false
	client := New(Config{Enabled: true, URL: server.URL, Timeout: time.Second})
	_, err := client.StoreToolImage(context.Background(), uploadHeader(t, "palsu.jpg", []byte("not-image")), t.TempDir(), func() (string, error) {
		fallbackCalled = true
		return "legacy.jpg", nil
	})
	if !errors.Is(err, ErrInvalidImage) {
		t.Fatalf("error = %v, ingin ErrInvalidImage", err)
	}
	if fallbackCalled {
		t.Fatal("fallback tidak boleh dipanggil untuk gambar invalid")
	}
}

func TestStoreToolImageFallsBackBeforeReadingOversizedUpload(t *testing.T) {
	file := uploadHeader(t, "besar.jpg", []byte("small-test-content"))
	file.Size = 2048
	fallbackCalled := false
	client := New(Config{Enabled: true, URL: "http://127.0.0.1:1", MaxUploadBytes: 1024})
	stored, err := client.StoreToolImage(context.Background(), file, t.TempDir(), func() (string, error) {
		fallbackCalled = true
		return "legacy.jpg", nil
	})
	if err != nil {
		t.Fatal(err)
	}
	if !fallbackCalled || stored.Filename != "legacy.jpg" || stored.Optimized {
		t.Fatalf("fallback upload besar tidak sesuai: %#v", stored)
	}
}
