<?php

namespace Tests\Feature;

use App\Http\Controllers\Sysadmin\MasterInstruksiKerjaCtrl;
use App\Http\Middleware\ValidateWorksheetInstructions;
use App\Services\WorksheetInstructionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class WorksheetInstructionAvailabilityTest extends TestCase
{
    private $instructionId;
    private $fileId;
    private $filename;
    private $testPublicPath;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('sqlite');
        Schema::create('instruksikerja_m', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('kdprofile');
            $table->boolean('statusenabled');
            $table->string('namainstruksikerja');
            $table->string('noisntruksikerja');
        });
        Schema::create('instruksikerja_file_t', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('kdprofile');
            $table->boolean('statusenabled');
            $table->integer('instruksikerjafk');
            $table->integer('versi');
            $table->string('namaasli');
            $table->string('namafile');
        });
        Schema::create('daftarinstruksikerja_t', function (Blueprint $table) {
            $table->string('norec')->primary();
            $table->boolean('statusenabled');
            $table->integer('idalatinstruksikerja');
            $table->string('detailregistrasifk');
        });
        $this->testPublicPath = sys_get_temp_dir() . '/worksheet-ik-' . bin2hex(random_bytes(8));
        mkdir($this->testPublicPath);
        mkdir($this->testPublicPath . '/berkas-mutu');
        mkdir($this->testPublicPath . '/berkas-instruksi-kerja');
        $this->app->instance('path.public', $this->testPublicPath);
        DB::beginTransaction();
        session(['kdProfile' => 1]);
        $this->instructionId = random_int(1000000000, 2000000000);
        $this->fileId = -$this->instructionId;
        $this->filename = 'worksheet-ik-test-' . $this->instructionId . '.pdf';
        DB::table('instruksikerja_m')->insert([
            'id' => $this->instructionId, 'kdprofile' => 1, 'statusenabled' => true,
            'namainstruksikerja' => 'Worksheet availability test',
            'noisntruksikerja' => 'TEST-' . $this->instructionId,
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        foreach (['berkas-mutu', 'berkas-instruksi-kerja'] as $directory) {
            $path = public_path($directory . '/' . $this->filename);
            if (is_file($path)) unlink($path);
            rmdir($this->testPublicPath . '/' . $directory);
        }
        rmdir($this->testPublicPath);
        parent::tearDown();
    }

    private function addFile(string $directory = 'berkas-mutu'): void
    {
        file_put_contents(public_path($directory . '/' . $this->filename), "%PDF-1.4\n% worksheet test\n");
        DB::table('instruksikerja_file_t')->insert([
            'id' => $this->fileId, 'kdprofile' => 1, 'statusenabled' => true,
            'instruksikerjafk' => $this->instructionId, 'versi' => 1,
            'namaasli' => $this->filename, 'namafile' => $this->filename,
        ]);
    }

    private function option(): array
    {
        return app(WorksheetInstructionService::class)->options(1, [$this->instructionId])->first();
    }

    private function submit($rows, ?string $detail = null, string $path = '/service/pelaksana/save-data-upload-lembar-kerja', string $method = 'POST')
    {
        return app(ValidateWorksheetInstructions::class)->handle(
            Request::create($path, $method, ['daftarinstruksikerja' => $rows, 'norec_detail' => $detail]),
            function () { return response()->json(['saved' => true]); }
        );
    }

    public function test_upload_immediately_enables_selection_and_json_and_multipart_saving(): void
    {
        $rows = [['instruksikerja' => $this->instructionId]];
        $this->assertTrue($this->option()['disabled']);
        $this->assertSame(422, $this->submit($rows)->getStatusCode());
        $this->assertSame(422, $this->submit(json_encode($rows))->getStatusCode());
        $this->addFile();
        $this->assertFalse($this->option()['disabled']);
        $this->assertSame('', $this->option()['reason']);
        $this->assertSame(200, $this->submit($rows)->getStatusCode());
        $this->assertSame(200, $this->submit(json_encode($rows))->getStatusCode());
    }

    public function test_inactive_and_cross_profile_uploads_are_rejected_but_remote_files_are_accepted(): void
    {
        $this->addFile();
        $this->assertFalse($this->option()['disabled']);
        DB::table('instruksikerja_file_t')->where('id', $this->fileId)->update(['statusenabled' => false]);
        $this->assertTrue($this->option()['disabled']);
        DB::table('instruksikerja_file_t')->where('id', $this->fileId)->update(['statusenabled' => true, 'kdprofile' => 2]);
        $this->assertTrue($this->option()['disabled']);
        DB::table('instruksikerja_file_t')->where('id', $this->fileId)->update(['kdprofile' => 1]);
        unlink(public_path('berkas-mutu/' . $this->filename));
        $this->assertFalse($this->option()['disabled'], 'Committed upload history must work even on a backend without the document disk');
        $this->assertSame(200, $this->submit([['instruksikerja' => $this->instructionId]])->getStatusCode());
    }

    public function test_legacy_files_work_but_inactive_instructions_are_rejected(): void
    {
        $this->addFile('berkas-instruksi-kerja');
        $this->assertFalse($this->option()['disabled']);
        DB::table('instruksikerja_m')->where('id', $this->instructionId)->update(['statusenabled' => false]);
        $this->assertTrue($this->option()['disabled']);
        $this->assertSame(422, $this->submit([['instruksikerja' => $this->instructionId]])->getStatusCode());
    }

    public function test_malformed_selection_is_rejected_but_empty_optional_rows_work(): void
    {
        $this->assertSame(422, $this->submit('{invalid')->getStatusCode());
        $this->assertSame(422, $this->submit([['instruksikerja' => 'unselected text']])->getStatusCode());
        $this->assertSame(200, $this->submit([['instruksikerja' => null]])->getStatusCode());
        $this->assertSame(200, $this->submit([])->getStatusCode());
    }

    public function test_dropdown_does_not_cache_and_all_worksheet_write_routes_have_the_guard(): void
    {
        $response = (new MasterInstruksiKerjaCtrl())->worksheetInstructions(Request::create('/'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $routes = collect(app('router')->getRoutes()->getRoutes())->filter(function ($route) {
            return preg_match('~/(pelaksana|penyelia)/(save-data-upload-lembar-kerja|update-metadata-lembar-kerja)~', $route->uri());
        });
        $this->assertGreaterThanOrEqual(62, $routes->count());
        foreach ($routes as $route) {
            $this->assertContains(ValidateWorksheetInstructions::class, $route->gatherMiddleware(), $route->uri());
        }
    }

    public function test_legacy_references_survive_updates_but_new_selections_require_uploads(): void
    {
        $oldId = $this->instructionId;
        DB::table('daftarinstruksikerja_t')->insert([
            'norec' => 'old-reference', 'statusenabled' => true,
            'idalatinstruksikerja' => $oldId, 'detailregistrasifk' => 'old-worksheet',
        ]);
        $newId = $oldId + 1;
        DB::table('instruksikerja_m')->insert([
            'id' => $newId, 'kdprofile' => 1, 'statusenabled' => true,
            'namainstruksikerja' => 'New selection without upload', 'noisntruksikerja' => 'NEW',
        ]);
        foreach (['pelaksana', 'penyelia'] as $role) {
            foreach (['update-metadata-lembar-kerja', 'save-data-upload-lembar-kerja-meter-sumber'] as $action) {
                $path = '/service/' . $role . '/' . $action;
                $this->assertSame(200, $this->submit([['instruksikerja' => $oldId]], 'old-worksheet', $path)->getStatusCode());
                $this->assertSame(422, $this->submit([['instruksikerja' => $newId]], 'old-worksheet', $path)->getStatusCode());
                $this->assertSame(422, $this->submit([['instruksikerja' => $oldId], ['instruksikerja' => $newId]], 'old-worksheet', $path)->getStatusCode());
                $this->assertSame(422, $this->submit([['instruksikerja' => $oldId]], 'different-worksheet', $path)->getStatusCode());
            }
        }
        $this->assertSame(1, DB::table('daftarinstruksikerja_t')->count());
        $this->assertEquals($oldId, DB::table('daftarinstruksikerja_t')->value('idalatinstruksikerja'));
    }

    public function test_old_certificate_prints_are_not_subject_to_upload_validation(): void
    {
        $rows = [['instruksikerja' => $this->instructionId]];
        foreach (['GET', 'POST'] as $method) {
            foreach (['pelaksana', 'penyelia'] as $role) {
                $this->assertSame(200, $this->submit($rows, 'old-worksheet', '/service/' . $role . '/cetak-sertifikat', $method)->getStatusCode());
            }
        }
    }
}
