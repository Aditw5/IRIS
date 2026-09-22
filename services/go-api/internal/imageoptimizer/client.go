package imageoptimizer

import (
	"bytes"
	"context"
	"errors"
	"fmt"
	"io"
	"log"
	"mime/multipart"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/google/uuid"
)

const maxOptimizerResponseBytes = 25 * 1024 * 1024

var ErrInvalidImage = errors.New("file harus berupa gambar JPEG atau PNG yang valid")

type Config struct {
	Enabled        bool
	URL            string
	Timeout        time.Duration
	MaxUploadBytes int64
}

type Client struct {
	enabled        bool
	url            string
	http           *http.Client
	maxUploadBytes int64
}

type StoredImage struct {
	Filename          string
	ThumbnailFilename string
	CreatedFiles      []string
	Optimized         bool
}

func New(cfg Config) *Client {
	timeout := cfg.Timeout
	if timeout <= 0 {
		timeout = 15 * time.Second
	}
	maxUploadBytes := cfg.MaxUploadBytes
	if maxUploadBytes <= 0 {
		maxUploadBytes = 20 * 1024 * 1024
	}
	return &Client{
		enabled:        cfg.Enabled,
		url:            strings.TrimRight(strings.TrimSpace(cfg.URL), "/") + "/v1/optimize",
		http:           &http.Client{Timeout: timeout},
		maxUploadBytes: maxUploadBytes,
	}
}

func (c *Client) StoreToolImage(
	ctx context.Context,
	file *multipart.FileHeader,
	destinationDir string,
	legacyStore func() (string, error),
) (StoredImage, error) {
	if c == nil || !c.enabled {
		return storeLegacy(legacyStore)
	}
	if file == nil {
		return storeLegacy(legacyStore)
	}
	if strings.EqualFold(filepath.Ext(file.Filename), ".webp") {
		return storeLegacy(legacyStore)
	}
	if file.Size > c.maxUploadBytes {
		return storeLegacy(legacyStore)
	}

	created := make([]string, 0, 2)
	fallback := func(cause error) (StoredImage, error) {
		for _, name := range created {
			_ = os.Remove(filepath.Join(destinationDir, name))
		}
		log.Printf("[image-optimizer] optimasi gambar alat gagal; menggunakan upload lama: %v", cause)
		return storeLegacy(legacyStore)
	}

	source, err := readUpload(file)
	if err != nil {
		return fallback(err)
	}
	mainBody, err := c.optimize(ctx, source, file.Filename, "main")
	if err != nil {
		if errors.Is(err, ErrInvalidImage) {
			return StoredImage{}, err
		}
		return fallback(err)
	}
	thumbBody, err := c.optimize(ctx, source, file.Filename, "thumbnail")
	if err != nil {
		if errors.Is(err, ErrInvalidImage) {
			return StoredImage{}, err
		}
		return fallback(err)
	}

	if err := os.MkdirAll(destinationDir, 0o755); err != nil {
		return fallback(err)
	}
	id := uuid.NewString()
	mainName := id + ".webp"
	thumbName := id + "-thumb.webp"
	if err := writeAtomically(filepath.Join(destinationDir, mainName), mainBody); err != nil {
		return fallback(err)
	}
	created = append(created, mainName)
	if err := writeAtomically(filepath.Join(destinationDir, thumbName), thumbBody); err != nil {
		return fallback(err)
	}
	created = append(created, thumbName)

	return StoredImage{
		Filename:          mainName,
		ThumbnailFilename: thumbName,
		CreatedFiles:      created,
		Optimized:         true,
	}, nil
}

func (c *Client) optimize(ctx context.Context, source []byte, filename, profile string) ([]byte, error) {
	var body bytes.Buffer
	writer := multipart.NewWriter(&body)
	if err := writer.WriteField("profile", profile); err != nil {
		return nil, err
	}
	part, err := writer.CreateFormFile("image", filepath.Base(filename))
	if err != nil {
		return nil, err
	}
	if _, err := part.Write(source); err != nil {
		return nil, err
	}
	if err := writer.Close(); err != nil {
		return nil, err
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, c.url, &body)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Content-Type", writer.FormDataContentType())

	resp, err := c.http.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		_, _ = io.Copy(io.Discard, io.LimitReader(resp.Body, 64*1024))
		if resp.StatusCode == http.StatusUnprocessableEntity {
			return nil, ErrInvalidImage
		}
		return nil, fmt.Errorf("image optimizer merespons HTTP %d", resp.StatusCode)
	}
	if !strings.HasPrefix(strings.ToLower(resp.Header.Get("Content-Type")), "image/webp") {
		return nil, errors.New("respons image optimizer bukan image/webp")
	}

	limited := io.LimitReader(resp.Body, maxOptimizerResponseBytes+1)
	result, err := io.ReadAll(limited)
	if err != nil {
		return nil, err
	}
	if len(result) > maxOptimizerResponseBytes {
		return nil, errors.New("respons image optimizer terlalu besar")
	}
	if !isWebP(result) {
		return nil, errors.New("respons image optimizer bukan WebP yang valid")
	}
	return result, nil
}

func readUpload(file *multipart.FileHeader) ([]byte, error) {
	if file == nil {
		return nil, errors.New("foto wajib dipilih")
	}
	source, err := file.Open()
	if err != nil {
		return nil, err
	}
	defer source.Close()
	return io.ReadAll(source)
}

func storeLegacy(store func() (string, error)) (StoredImage, error) {
	filename, err := store()
	if err != nil {
		return StoredImage{}, err
	}
	return StoredImage{Filename: filename, CreatedFiles: []string{filename}}, nil
}

func writeAtomically(path string, contents []byte) error {
	temporary, err := os.CreateTemp(filepath.Dir(path), ".image-optimizer-*.tmp")
	if err != nil {
		return err
	}
	temporaryPath := temporary.Name()
	defer os.Remove(temporaryPath)

	if _, err := temporary.Write(contents); err != nil {
		temporary.Close()
		return err
	}
	if err := temporary.Chmod(0o644); err != nil {
		temporary.Close()
		return err
	}
	if err := temporary.Sync(); err != nil {
		temporary.Close()
		return err
	}
	if err := temporary.Close(); err != nil {
		return err
	}
	return os.Rename(temporaryPath, path)
}

func isWebP(body []byte) bool {
	return len(body) >= 12 && string(body[0:4]) == "RIFF" && string(body[8:12]) == "WEBP"
}
