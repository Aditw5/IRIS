<?php

namespace Tests\Unit;

use App\Exceptions\InvalidToolImageException;
use App\Services\ImageOptimizerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImageOptimizerServiceTest extends TestCase
{
    private $originalPublicPath;
    private $temporaryPublicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalPublicPath = public_path();
        $this->temporaryPublicPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ulab-image-optimizer-' . Str::uuid();
        File::makeDirectory($this->temporaryPublicPath . DIRECTORY_SEPARATOR . 'produk', 0755, true);
        $this->app->instance('path.public', $this->temporaryPublicPath);
        config([
            'image_optimizer.url' => 'http://image-optimizer.test',
            'image_optimizer.timeout' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $this->app->instance('path.public', $this->originalPublicPath);
        File::deleteDirectory($this->temporaryPublicPath);
        parent::tearDown();
    }

    public function test_it_stores_webp_main_and_thumbnail(): void
    {
        config(['image_optimizer.enabled' => true]);
        $webp = 'RIFF' . pack('V', 4) . 'WEBPVP8 ';
        Http::fake(fn() => Http::response($webp, 200, ['Content-Type' => 'image/webp']));

        $result = app(ImageOptimizerService::class)->storeToolImage(
            UploadedFile::fake()->createWithContent('alat.jpg', 'jpeg-content')
        );

        $this->assertTrue($result['optimized']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]+\.webp$/', $result['filename']);
        $this->assertSame(str_replace('.webp', '-thumb.webp', $result['filename']), $result['thumbnail']);
        $this->assertFileExists(public_path('produk/' . $result['filename']));
        $this->assertFileExists(public_path('produk/' . $result['thumbnail']));
        Http::assertSentCount(2);
    }

    public function test_it_uses_legacy_upload_when_optimizer_fails(): void
    {
        config(['image_optimizer.enabled' => true]);
        Http::fake(fn() => Http::response('down', 503));

        $result = app(ImageOptimizerService::class)->storeToolImage(
            UploadedFile::fake()->createWithContent('alat.png', 'png-content')
        );

        $this->assertFalse($result['optimized']);
        $this->assertStringEndsWith('_alat.png', $result['filename']);
        $this->assertFileExists(public_path('produk/' . $result['filename']));
    }

    public function test_it_uses_legacy_upload_when_optimizer_is_disabled(): void
    {
        config(['image_optimizer.enabled' => false]);

        $result = app(ImageOptimizerService::class)->storeToolImage(
            UploadedFile::fake()->createWithContent('alat.jpg', 'jpeg-content')
        );

        $this->assertFalse($result['optimized']);
        $this->assertStringEndsWith('_alat.jpg', $result['filename']);
        $this->assertFileExists(public_path('produk/' . $result['filename']));
        Http::assertNothingSent();
    }

    public function test_it_rejects_invalid_image_response_without_fallback(): void
    {
        config(['image_optimizer.enabled' => true]);
        Http::fake(fn() => Http::response(['detail' => 'invalid image'], 422));

        $this->expectException(InvalidToolImageException::class);
        $this->expectExceptionMessage('File harus berupa gambar JPEG atau PNG yang valid.');

        app(ImageOptimizerService::class)->storeToolImage(
            UploadedFile::fake()->createWithContent('palsu.jpg', 'bukan-gambar')
        );
    }

    public function test_large_upload_uses_legacy_path_without_loading_optimizer(): void
    {
        config([
            'image_optimizer.enabled' => true,
            'image_optimizer.max_upload_bytes' => 1024,
        ]);

        $file = UploadedFile::fake()->createWithContent('besar.jpg', 'jpeg-content')->size(2);
        $result = app(ImageOptimizerService::class)->storeToolImage($file);

        $this->assertFalse($result['optimized']);
        $this->assertFileExists(public_path('produk/' . $result['filename']));
        Http::assertNothingSent();
    }
}
