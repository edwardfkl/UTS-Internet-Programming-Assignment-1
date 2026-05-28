<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_formats_aud_with_prefix_and_two_decimals(): void
    {
        $this->assertSame('A$99.90', Money::formatAud(99.9));
        $this->assertSame('A$899.00', Money::formatAud(899));
        $this->assertSame('A$249.50', Money::formatAud('249.50'));
    }

    public function test_formats_null_or_blank_as_zero(): void
    {
        $this->assertSame('A$0.00', Money::formatAud(null));
        $this->assertSame('A$0.00', Money::formatAud(''));
    }

    public function test_formats_zero_and_whole_dollar_amounts(): void
    {
        $this->assertSame('A$0.00', Money::formatAud(0));
        $this->assertSame('A$10.00', Money::formatAud(10));
    }

    public function test_formats_large_catalogue_prices(): void
    {
        $this->assertSame('A$1,899.00', Money::formatAud(1899));
    }

    public function test_rounds_to_two_decimal_places(): void
    {
        $this->assertSame('A$12.35', Money::formatAud(12.345));
        $this->assertSame('A$12.34', Money::formatAud(12.344));
    }

    #[DataProvider('numericInputProvider')]
    public function test_accepts_numeric_string_inputs(float|int|string $input, string $expected): void
    {
        $this->assertSame($expected, Money::formatAud($input));
    }

    public static function numericInputProvider(): array
    {
        return [
            'integer string' => ['42', 'A$42.00'],
            'decimal string' => ['19.9', 'A$19.90'],
            'float' => [7.5, 'A$7.50'],
        ];
    }
}
