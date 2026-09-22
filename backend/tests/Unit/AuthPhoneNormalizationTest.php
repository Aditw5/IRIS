<?php

namespace Tests\Unit;

use App\Http\Controllers\Auth\AuthCtrl;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AuthPhoneNormalizationTest extends TestCase
{
    /** @dataProvider phoneProvider */
    public function test_phone_is_normalized_to_indonesian_country_code(string $input, string $expected): void
    {
        $reflection = new ReflectionClass(AuthCtrl::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('normalizePhone');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($controller, $input));
    }

    public static function phoneProvider(): array
    {
        return [
            'national format' => ['0812-1000-0284', '6281210000284'],
            'subscriber format' => ['81210000284', '6281210000284'],
            'international format' => ['+62 812-1000-0284', '6281210000284'],
        ];
    }
}
