<?php

namespace App\Services;

use App\Exceptions\InvalidToolImageException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImageOptimizerService
{
    public function storeToolImage(UploadedFile $file): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());
        $maxUploadBytes = (int) config('image_optimizer.max_upload_bytes');
        if (!config('image_optimizer.enabled')
            || $extension === 'webp'
            || ($maxUploadBytes > 0 && $file->getSize() > $maxUploadBytes)
        ) {
            return $this->storeLegacy($file);
        }

        $uuid = (string) Str::uuid();
        $mainFilename = $uuid . '.webp';
        $thumbnailFilename = $uuid . '-thumb.webp';
        $createdPaths = [];

        try {
            $contents = file_get_contents($file->getRealPath());
            if ($contents === false) {
                throw new RuntimeException('File upload tidak dapat dibaca');
            }

            $main = $this->optimize($contents, $file->getClientOriginalName(), 'main');
            $thumbnail = $this->optimize($contents, $file->getClientOriginalName(), 'thumbnail');

            $directory = public_path('produk');
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('Folder produk tidak dapat dibuat');
            }

            $mainPath = $directory . DIRECTORY_SEPARATOR . $mainFilename;
            $thumbnailPath = $directory . DIRECTORY_SEPARATOR . $thumbnailFilename;
            $this->writeAtomically($mainPath, $main);
            $createdPaths[] = $mainPath;
            $this->writeAtomically($thumbnailPath, $thumbnail);
            $createdPaths[] = $thumbnailPath;

            return [
                'filename' => $mainFilename,
                'thumbnail' => $thumbnailFilename,
                'paths' => $createdPaths,
                'optimized' => true,
            ];
        } catch (InvalidToolImageException $e) {
            foreach ($createdPaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            throw $e;
        } catch (Throwable $e) {
            foreach ($createdPaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            Log::warning('Optimasi gambar alat gagal; menggunakan upload lama.', [
                'error' => $e->getMessage(),
                'original_name' => $file->getClientOriginalName(),
            ]);

            return $this->storeLegacy($file);
        }
    }

    public function thumbnailFilename(?string $filename): ?string
    {
        $filename = trim((string) $filename);
        if ($filename === '' || !Str::endsWith(Str::lower($filename), '.webp')) {
            return null;
        }

        $thumbnail = substr($filename, 0, -5) . '-thumb.webp';
        return is_file(public_path('produk/' . $thumbnail)) ? $thumbnail : null;
    }

    private function optimize(string $contents, string $originalName, string $profile): string
    {
        $url = rtrim((string) config('image_optimizer.url'), '/') . '/v1/optimize';
        $timeout = (int) config('image_optimizer.timeout');

        /** @var Response $response */
        $response = Http::withOptions(['connect_timeout' => $timeout])
            ->timeout($timeout)
            ->attach('image', $contents, $originalName)
            ->post($url, ['profile' => $profile]);

        if ($response->status() === 422) {
            throw new InvalidToolImageException('File harus berupa gambar JPEG atau PNG yang valid.');
        }
        if (!$response->successful()) {
            throw new RuntimeException('Image optimizer merespons HTTP ' . $response->status());
        }

        $body = $response->body();
        $contentType = Str::lower((string) $response->header('Content-Type'));
        if (!Str::startsWith($contentType, 'image/webp') || !$this->isWebp($body)) {
            throw new RuntimeException('Respons image optimizer bukan WebP yang valid');
        }

        return $body;
    }

    private function storeLegacy(UploadedFile $file): array
    {
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $file->move(public_path('produk'), $filename);
        $path = public_path('produk/' . $filename);

        return [
            'filename' => $filename,
            'thumbnail' => null,
            'paths' => [$path],
            'optimized' => false,
        ];
    }

    private function writeAtomically(string $path, string $contents): void
    {
        $temporary = $path . '.tmp-' . bin2hex(random_bytes(6));
        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Gagal menulis hasil optimasi gambar');
        }

        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Gagal menyimpan hasil optimasi gambar');
        }
    }

    private function isWebp(string $body): bool
    {
        return strlen($body) >= 12
            && substr($body, 0, 4) === 'RIFF'
            && substr($body, 8, 4) === 'WEBP';
    }
}
