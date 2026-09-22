<?php

namespace Tests\Unit;

use App\Http\Controllers\RBK\RbkCtrl;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RbkPbjPpnAllocationTest extends TestCase
{
    private function allocator(): array
    {
        $reflection = new ReflectionClass(RbkCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('alokasikanTotalEstimasiPbj');
        $method->setAccessible(true);

        return [$controller, $method];
    }

    private function detail(float $jumlah, string $hargaSatuan): object
    {
        return (object) [
            'banyak' => $jumlah,
            'hargasatuan' => $hargaSatuan,
        ];
    }

    public function testJumlahDetailSamaPersisDenganTotalEstimasiPbj(): void
    {
        [$controller, $method] = $this->allocator();

        $details = new Collection([
            $this->detail(8, '56000'),
            $this->detail(5, '35000'),
            $this->detail(4, '50000'),
            $this->detail(2, '173000'),
            $this->detail(5, '49500'),
            $this->detail(4, '42000'),
            $this->detail(4, '27000'),
        ]);

        $result = $method->invoke($controller, $details, 12);

        $this->assertEquals(1692500, $result['subtotal']);
        $this->assertEquals(203100, $result['nilai_ppn']);
        $this->assertEquals(1895600, $result['total_estimasi']);
        $this->assertEquals(1895600, $result['details']->sum('total_estimasi'));
        $this->assertEquals(203100, $result['details']->sum('nilai_ppn'));
    }

    public function testPpnNolMenggunakanSubtotalLangsung(): void
    {
        [$controller, $method] = $this->allocator();

        $details = new Collection([
            $this->detail(2, '100000'),
            $this->detail(1, '50000'),
        ]);

        $result = $method->invoke($controller, $details, 0);

        $this->assertEquals(250000, $result['subtotal']);
        $this->assertEquals(0, $result['nilai_ppn']);
        $this->assertEquals(250000, $result['total_estimasi']);
        $this->assertEquals(250000, $result['details']->sum('total_estimasi'));
    }
}
