<?php

namespace Tests\Unit;

use App\Http\Controllers\RBK\RbkCtrl;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RbkPbjMatchingTest extends TestCase
{
    private function matcher(): array
    {
        $reflection = new ReflectionClass(RbkCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('matchPbjToRbk');
        $method->setAccessible(true);

        return [$controller, $method];
    }

    public function testPrkSamaDipisahkanDenganCostCodeParsialDanUserUnit(): void
    {
        [$controller, $method] = $this->matcher();

        $headers = new Collection([
            (object) [
                'norec' => 'rbk-unit-1',
                'unitfk' => 1,
                'no_prk' => '262K0101',
                'cost_code' => 'AAA111111999F104/JBJSTK',
            ],
            (object) [
                'norec' => 'rbk-unit-2',
                'unitfk' => 2,
                'no_prk' => '262K0101',
                'cost_code' => 'BBB222222888F104/JBJSTK',
            ],
        ]);

        $units = (new Collection([
            (object) ['id' => '1', 'namaperusahaan' => 'UNIT SATU'],
            (object) ['id' => '2', 'namaperusahaan' => 'UNIT DUA'],
        ]))->keyBy('id');

        $matched = $method->invoke($controller, (object) [
            'user' => 2,
            'usermanual' => null,
            'prk' => '262K0101',
            'costcode' => 'BBB222222888',
        ], $headers, $units);

        $this->assertSame('rbk-unit-2', $matched[0]->norec);
        $this->assertSame('user_unit+prk+cost_code', $matched[1]);
    }

    public function testPenandaBertentanganTidakDipaksakanMasuk(): void
    {
        [$controller, $method] = $this->matcher();

        $headers = new Collection([
            (object) [
                'norec' => 'rbk-unit-1',
                'unitfk' => 1,
                'no_prk' => '262K0101',
                'cost_code' => 'AAA111111999F104/JBJSTK',
            ],
            (object) [
                'norec' => 'rbk-unit-2',
                'unitfk' => 2,
                'no_prk' => '262K0101',
                'cost_code' => 'BBB222222888F104/JBJSTK',
            ],
        ]);

        $units = (new Collection([
            (object) ['id' => '1', 'namaperusahaan' => 'UNIT SATU'],
            (object) ['id' => '2', 'namaperusahaan' => 'UNIT DUA'],
        ]))->keyBy('id');

        $conflict = $method->invoke($controller, (object) [
            'user' => 2,
            'usermanual' => null,
            'prk' => '262K0101',
            'costcode' => 'AAA111111999',
        ], $headers, $units);

        $wrongUnit = $method->invoke($controller, (object) [
            'user' => 1,
            'usermanual' => null,
            'prk' => 'PRK-BEDA',
            'costcode' => 'BBB222222888',
        ], $headers, $units);

        $this->assertNull($conflict);
        $this->assertNull($wrongUnit);
    }

    public function testUnitRepairNonSurkesTetapDapatDicocokkanJikaTerdaftarDiRbk(): void
    {
        [$controller, $method] = $this->matcher();

        $headers = new Collection([
            (object) [
                'norec' => 'rbk-repair-unit',
                'unitfk' => 77,
                'no_prk' => '262L0303',
                'cost_code' => 'ACR102060001821F106',
            ],
        ]);

        $units = (new Collection([
            (object) [
                'id' => '77',
                'namaperusahaan' => 'REPAIR UNIT CONTOH',
                'issurkes' => false,
            ],
        ]))->keyBy('id');

        $matched = $method->invoke($controller, (object) [
            'user' => 77,
            'usermanual' => null,
            'prk' => '262L0303',
            'costcode' => 'ACR102060001821F106',
        ], $headers, $units);

        $this->assertSame('rbk-repair-unit', $matched[0]->norec);
        $this->assertSame('user_unit+prk+cost_code', $matched[1]);
    }

    public function testKategoriKosongPbjMaterialDipulihkanSebagaiMaterial(): void
    {
        $reflection = new ReflectionClass(RbkCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('tentukanKategoriRbkDetail');
        $method->setAccessible(true);

        $kategori = $method->invoke($controller, (object) [
            'jenis_pbj' => 'MATERIAL',
            'namaitem' => 'Battery Spring Contact Set',
            'uraianitem' => 'Material perbaikan Fluke 381',
            'grupmobilisasi' => null,
            'judulmobilisasi' => null,
        ]);

        $this->assertSame('material', $kategori);
    }
}
