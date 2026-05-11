<?php

namespace Tests\Unit;

use App\Support\PromoCode;
use PHPUnit\Framework\TestCase;

class PromoCodeNormaliseTest extends TestCase
{
    public function test_returns_null_for_null_input(): void
    {
        $this->assertNull(PromoCode::normalise(null));
    }

    public function test_returns_null_for_blank_string(): void
    {
        $this->assertNull(PromoCode::normalise(''));
        $this->assertNull(PromoCode::normalise('   '));
        $this->assertNull(PromoCode::normalise("\t\n"));
    }

    public function test_uppercases_lower_or_mixed_case_codes(): void
    {
        $this->assertSame('WELCOME10', PromoCode::normalise('welcome10'));
        $this->assertSame('SAVE20', PromoCode::normalise('Save20'));
    }

    public function test_trims_surrounding_whitespace(): void
    {
        $this->assertSame('WELCOME10', PromoCode::normalise('  welcome10  '));
        $this->assertSame('SAVE20', PromoCode::normalise("\tsave20\n"));
    }

    public function test_preserves_dashes_and_underscores(): void
    {
        $this->assertSame('NEW-USER_2026', PromoCode::normalise('new-user_2026'));
    }
}
