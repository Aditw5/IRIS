<?php

namespace Tests\Unit;

use App\Http\Controllers\Pbj\PengajuanPbjCtrl;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PbjRbkCategoryTest extends TestCase
{
    /**
     * @dataProvider kategoriProvider
     */
    public function testPenentuanKategoriRbk(array $item, ?string $jenisPbj, string $expected): void
    {
        $controllerReflection = new ReflectionClass(PengajuanPbjCtrl::class);
        $controller = $controllerReflection->newInstanceWithoutConstructor();
        $method = $controllerReflection->getMethod('tentukanKategoriRbk');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($controller, $item, $jenisPbj));
    }

    public function kategoriProvider(): array
    {
        return [
            'penugasan kalibrasi tool Belawan' => [
                [
                    'namaitem' => 'Jasa MKP Penugasan Kalibrasi Tool UP Belawan',
                    'uraianitem' => 'Tool dibawa ke Lab UMRO Jakarta selama 2 hari',
                ],
                'JASA',
                'mobilisasi',
            ],
            'penugasan pengambilan tool unit lain' => [
                [
                    'namaitem' => 'Jasa SPPD Personil MKP',
                    'uraianitem' => 'Penugasan Pengambilan Tool untuk Kalibrasi UP Brantas',
                ],
                'JASA',
                'mobilisasi',
            ],
            'penugasan pekerjaan biasa tetap manpower' => [
                [
                    'namaitem' => 'Penugasan Personil MKP untuk Kolaborasi Inovasi',
                    'uraianitem' => 'Pengambilan data inovasi ASCI',
                ],
                'JASA',
                'manpower',
            ],
            'material tetap lebih diprioritaskan' => [
                [
                    'namaitem' => 'Penugasan Kalibrasi Tool',
                    'uraianitem' => 'Pengambilan alat',
                ],
                'MATERIAL',
                'material',
            ],
            'grup mobilisasi eksplisit' => [
                [
                    'namaitem' => 'Biaya perjalanan',
                    'grupmobilisasi' => 'group-a',
                ],
                'JASA',
                'mobilisasi',
            ],
        ];
    }
}
