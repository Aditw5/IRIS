<?php

namespace Tests\Unit;

use App\Http\Controllers\Landing\NoAuthCtrl;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MeasurementCorrectionTest extends TestCase
{
    public function testItNormalizesCorrectionAndInstrumentReading(): void
    {
        $reflection = new ReflectionClass(NoAuthCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('normalizeMeasurementCorrection');
        $method->setAccessible(true);

        $result = $method->invoke(
            $controller,
            (object) [
                'norec' => 'row-1',
                'pembacaan_alat' => '10,25',
                'pembacaan_alat_satuan' => 'V',
                'koreksi' => '-0,15',
                'koreksi_satuan' => 'V',
                'created_at' => '2026-06-18 08:00:00',
            ],
            'lembarkerja_t',
            (object) ['noorderalat' => 'ORDER-OLD']
        );

        $this->assertSame(10.25, $result['reference_value']);
        $this->assertSame(-0.15, $result['correction_value']);
        $this->assertSame('ORDER-OLD', $result['source_noorder']);
        $this->assertSame('V', $result['correction_unit']);
        $this->assertSame('2026-06-18 08:00:00', $result['source_created_at']);
    }

    public function testItIgnoresRowsWithoutNumericCorrection(): void
    {
        $reflection = new ReflectionClass(NoAuthCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('normalizeMeasurementCorrection');
        $method->setAccessible(true);

        $result = $method->invoke(
            $controller,
            (object) [
                'pembacaan_alat' => '10 V',
                'koreksi' => '-',
            ],
            'lembarkerja_t',
            (object) ['noorderalat' => 'ORDER-OLD']
        );

        $this->assertNull($result);
    }

    public function testItNormalizesKalibrasiAiCorrectionWithUnitsInRawText(): void
    {
        $reflection = new ReflectionClass(NoAuthCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('normalizeMeasurementCorrection');
        $method->setAccessible(true);

        $result = $method->invoke(
            $controller,
            (object) [
                'id' => 'row-ai',
                'noorder' => 'ORDER-AI',
                'rentang' => '5 V',
                'pembacaan_alat' => '2,5 V',
                'koreksi' => '0,0046 V',
            ],
            'kalibrasi_ai',
            (object) ['noorderalat' => 'ORDER-AI']
        );

        $this->assertSame('ORDER-AI', $result['source_noorder']);
        $this->assertSame('kalibrasi_ai', $result['worksheet']);
        $this->assertSame('row-ai', $result['row_id']);
        $this->assertSame(5.0, $result['range_value']);
        $this->assertSame('V', $result['range_unit']);
        $this->assertSame(2.5, $result['reference_value']);
        $this->assertSame('V', $result['reference_unit']);
        $this->assertSame(0.0046, $result['correction_value']);
        $this->assertSame('V', $result['correction_unit']);
    }

    public function testItChoosesNewestMeasurementCandidate(): void
    {
        $reflection = new ReflectionClass(NoAuthCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('newestMeasurementCandidate');
        $method->setAccessible(true);

        $result = $method->invokeArgs($controller, [
            [
                'source' => 'pendaftaran',
                'table' => 'lembarkerja_t',
                'source_created_at' => '2026-06-17 10:00:00',
                'corrections' => [
                    ['reference_value' => 10, 'correction_value' => -0.1],
                ],
            ],
            [
                'source' => 'kalibrasi_ai',
                'table' => 'kalibrasi_ai',
                'source_created_at' => '2026-06-18 10:00:00',
                'corrections' => [
                    ['reference_value' => 10, 'correction_value' => -0.2],
                ],
            ],
        ]);

        $this->assertSame('kalibrasi_ai', $result['source']);
        $this->assertSame('kalibrasi_ai', $result['table']);
    }
}
