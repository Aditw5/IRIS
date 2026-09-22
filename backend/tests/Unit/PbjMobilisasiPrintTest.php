<?php

namespace Tests\Unit;

use App\Http\Controllers\Pbj\PengajuanPbjCtrl;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PbjMobilisasiPrintTest extends TestCase
{
    public function testCetakMempertahankanItemLamaDanMeringkasMobilisasi(): void
    {
        $controllerReflection = new ReflectionClass(PengajuanPbjCtrl::class);
        $controller = $controllerReflection->newInstanceWithoutConstructor();
        $method = $controllerReflection->getMethod('ringkasMobilisasiUntukCetak');
        $method->setAccessible(true);

        $details = new Collection([
            (object) [
                'norec' => 'lama-1',
                'namaitem' => 'Item Lama',
                'uraianitem' => 'Tetap per baris',
                'stockcode' => 'OLD',
                'banyak' => 2,
                'satuan' => 'PCS',
                'hargasatuan' => 1000,
                'keterangan' => 'Tidak berubah',
                'grupmobilisasi' => null,
                'judulmobilisasi' => null,
            ],
            (object) [
                'norec' => 'mob-1',
                'namaitem' => 'Transportasi',
                'uraianitem' => 'Tiket pesawat',
                'stockcode' => null,
                'banyak' => 2,
                'satuan' => 'TIKET',
                'hargasatuan' => 100000,
                'keterangan' => null,
                'grupmobilisasi' => 'group-a',
                'judulmobilisasi' => 'Mobilisasi UP Tenayan',
                'uraianmobilisasi' => 'Pengiriman dan perjalanan alat kalibrasi',
                'keteranganmobilisasi' => 'Dilaksanakan dalam satu paket mobilisasi',
            ],
            (object) [
                'norec' => 'mob-2',
                'namaitem' => 'Transportasi',
                'uraianitem' => 'Travel darat',
                'stockcode' => null,
                'banyak' => 1,
                'satuan' => 'TRIP',
                'hargasatuan' => 50000,
                'keterangan' => null,
                'grupmobilisasi' => 'group-a',
                'judulmobilisasi' => 'Mobilisasi UP Tenayan',
                'uraianmobilisasi' => 'Pengiriman dan perjalanan alat kalibrasi',
                'keteranganmobilisasi' => 'Dilaksanakan dalam satu paket mobilisasi',
            ],
        ]);

        $printed = $method->invoke($controller, $details);

        $this->assertCount(2, $printed);
        $this->assertSame('Item Lama', $printed[0]->namaitem);
        $this->assertSame(2, $printed[0]->banyak);
        $this->assertSame('PCS', $printed[0]->satuan);
        $this->assertSame(1000, $printed[0]->hargasatuan);

        $this->assertSame('Mobilisasi UP Tenayan', $printed[1]->namaitem);
        $this->assertSame('Pengiriman dan perjalanan alat kalibrasi', $printed[1]->uraianitem);
        $this->assertSame('Dilaksanakan dalam satu paket mobilisasi', $printed[1]->keterangan);
        $this->assertSame(1, $printed[1]->banyak);
        $this->assertSame('LOT', $printed[1]->satuan);
        $this->assertEquals(250000, $printed[1]->hargasatuan);
        $this->assertSame(2, $printed[1]->jumlahsubitem);
    }
}
