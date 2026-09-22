<?php

namespace Tests\Unit;

use App\Http\Controllers\Landing\NoAuthCtrl;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class KalibrasiAiThermalTest extends TestCase
{
    private function invoke(string $methodName, array $arguments)
    {
        $reflection = new ReflectionClass(NoAuthCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($controller, $arguments);
    }

    public function testItRecognizesThermalPayload(): void
    {
        $type = $this->invoke('kalibrasiAiType', [[
            'jenis' => 'thermal',
            'lingkup' => 'suhu',
            'set_point' => '50 °C',
        ], false, false]);

        $this->assertSame('thermal', $type);
    }

    public function testItRecognizesThermalOrderWithoutSpecialPayloadKeys(): void
    {
        $type = $this->invoke('kalibrasiAiType', [[
            'rentang' => '50 °C',
        ], false, true]);

        $this->assertSame('thermal', $type);
    }

    public function testItMapsThermalFieldsAndKeepsGenericCompatibility(): void
    {
        $values = $this->invoke('kalibrasiAiValues', [[
            'set_point' => '50 °C',
            'temperature_reference' => '50.1 °C',
            'temperature_uut' => '49.8 °C',
        ], 'thermal']);

        $this->assertSame('50 °C', $values['set_point']);
        $this->assertSame('50.1 °C', $values['temperature_reference']);
        $this->assertSame('49.8 °C', $values['temperature_uut']);
        $this->assertSame($values['set_point'], $values['rentang']);
        $this->assertSame($values['temperature_reference'], $values['penunjukan_standar']);
        $this->assertSame($values['temperature_uut'], $values['pembacaan_alat']);
        $this->assertNull($values['frekuensi']);
        $this->assertNull($values['vibrasi_reference']);
        $this->assertNull($values['uut']);
    }

    public function testVibrationMappingRemainsUnchanged(): void
    {
        $values = $this->invoke('kalibrasiAiValues', [[
            'frekuensi' => '40 Hz',
            'vibrasi_reference' => '100 mV',
            'uut' => '0.9 g',
        ], 'vibration']);

        $this->assertSame('40 Hz', $values['frekuensi']);
        $this->assertSame('100 mV', $values['vibrasi_reference']);
        $this->assertSame('0.9 g', $values['uut']);
        $this->assertSame($values['frekuensi'], $values['rentang']);
        $this->assertNull($values['set_point']);
    }
}
