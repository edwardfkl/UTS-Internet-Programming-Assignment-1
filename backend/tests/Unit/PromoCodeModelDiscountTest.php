<?php

namespace Tests\Unit;

use App\Models\PromoCode;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pure logic tests for {@see PromoCode::discountFor()} that use in-memory
 * (unsaved) Eloquent instances — no database required.
 */
class PromoCodeModelDiscountTest extends TestCase
{
    public function test_inactive_promo_returns_null(): void
    {
        $promo = new PromoCode([
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10,
            'is_active' => false,
        ]);

        $this->assertNull($promo->discountFor(100));
    }

    public function test_fixed_discount_is_clamped_to_subtotal(): void
    {
        $promo = new PromoCode([
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 50,
            'is_active' => true,
        ]);

        $this->assertSame(50.0, $promo->discountFor(150));
        $this->assertSame(20.0, $promo->discountFor(20));
    }

    public function test_percent_discount_computes_and_rounds(): void
    {
        $promo = new PromoCode([
            'type' => PromoCode::TYPE_PERCENT,
            'amount' => 10,
            'is_active' => true,
        ]);

        $this->assertSame(12.35, $promo->discountFor(123.45));
    }

    public function test_min_subtotal_gate(): void
    {
        $promo = new PromoCode([
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 20,
            'min_subtotal' => 100,
            'is_active' => true,
        ]);

        $this->assertNull($promo->discountFor(50));
        $this->assertSame(20.0, $promo->discountFor(100));
        $this->assertSame(20.0, $promo->discountFor(120));
    }

    public function test_starts_at_blocks_future_codes(): void
    {
        Carbon::setTestNow('2026-06-01 12:00:00');

        $promo = new PromoCode([
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10,
            'is_active' => true,
            'starts_at' => Carbon::parse('2026-06-02 00:00:00'),
        ]);

        $this->assertNull($promo->discountFor(100));

        Carbon::setTestNow();
    }

    public function test_ends_at_blocks_expired_codes(): void
    {
        Carbon::setTestNow('2026-06-01 12:00:00');

        $promo = new PromoCode([
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10,
            'is_active' => true,
            'ends_at' => Carbon::parse('2026-05-30 23:59:59'),
        ]);

        $this->assertNull($promo->discountFor(100));

        Carbon::setTestNow();
    }

    public function test_zero_subtotal_returns_zero_discount(): void
    {
        $promo = new PromoCode([
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10,
            'is_active' => true,
        ]);

        $this->assertSame(0.0, $promo->discountFor(0));
        $this->assertSame(0.0, $promo->discountFor(-5));
    }
}
