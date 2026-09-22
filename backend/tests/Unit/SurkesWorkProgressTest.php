<?php

namespace Tests\Unit;

use App\Http\Controllers\General\SysAdminCtrl;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SurkesWorkProgressTest extends TestCase
{
    private function resolve($row): array
    {
        $reflection = new ReflectionClass(SysAdminCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('resolveSurkesWorkProgress');
        $method->setAccessible(true);

        return $method->invoke($controller, $row);
    }

    public function test_unregistered_plan_has_zero_progress(): void
    {
        $result = $this->resolve(null);

        $this->assertSame(0, $result['progress_persen']);
        $this->assertSame('Belum masuk pendaftaran tahun ini', $result['status_terakhir']);
    }

    public function test_calibration_progress_follows_latest_completed_stage(): void
    {
        $row = (object) [
            'jenisorder' => 'kalibrasi',
            'tglkajiulang' => '2026-08-01 08:00:00',
            'tglverifasman' => '2026-08-01 09:00:00',
            'tglverifpenyelia' => '2026-08-01 10:00:00',
            'tglverifpelaksana' => '2026-08-01 11:00:00',
            'tglisilembarkerjapelaksana' => null,
        ];

        $result = $this->resolve($row);

        $this->assertSame(50, $result['progress_persen']);
        $this->assertSame('Sertifikat/lembar kerja belum diisi Pelaksana', $result['status_terakhir']);
    }

    public function test_completed_repair_has_full_progress(): void
    {
        $row = (object) [
            'jenisorder' => 'repair',
            'tglsetujumanagerlaporanrepair' => '2026-08-01 16:00:00',
        ];

        $result = $this->resolve($row);

        $this->assertSame(100, $result['progress_persen']);
        $this->assertSame('Selesai - Laporan repair disetujui Manager', $result['status_terakhir']);
    }
}
