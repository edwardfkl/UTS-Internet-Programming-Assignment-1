<?php

namespace Tests\Feature;

use App\Models\PromoCode;
use App\Support\PromoCode as PromoCodeFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromoCodeLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalise_uppercases_and_trims_codes(): void
    {
        $this->assertSame('WELCOME10', PromoCodeFacade::normalise(' welcome10 '));
        $this->assertSame('SAVE20', PromoCodeFacade::normalise('save20'));
        $this->assertNull(PromoCodeFacade::normalise(null));
        $this->assertNull(PromoCodeFacade::normalise('   '));
    }

    public function test_fixed_discount_is_capped_to_subtotal(): void
    {
        PromoCode::query()->create([
            'code' => 'BIG50',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 50.00,
            'is_active' => true,
        ]);

        $this->assertSame(50.0, PromoCodeFacade::discountFor('BIG50', 150.00));
        $this->assertSame(20.0, PromoCodeFacade::discountFor('BIG50', 20.00));
    }

    public function test_percent_discount_rounds_to_two_decimals(): void
    {
        PromoCode::query()->create([
            'code' => 'TENPCT',
            'type' => PromoCode::TYPE_PERCENT,
            'amount' => 10.00,
            'is_active' => true,
        ]);

        $this->assertSame(12.35, PromoCodeFacade::discountFor('TENPCT', 123.45));
    }

    public function test_min_subtotal_blocks_redemption_below_threshold(): void
    {
        PromoCode::query()->create([
            'code' => 'OVER100',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 20.00,
            'min_subtotal' => 100.00,
            'is_active' => true,
        ]);

        $this->assertNull(PromoCodeFacade::discountFor('OVER100', 50.00));
        $this->assertSame(20.0, PromoCodeFacade::discountFor('OVER100', 100.00));
    }

    public function test_inactive_code_is_not_redeemable(): void
    {
        PromoCode::query()->create([
            'code' => 'OFFLINE',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10.00,
            'is_active' => false,
        ]);

        $this->assertNull(PromoCodeFacade::discountFor('OFFLINE', 100.00));
    }

    public function test_date_window_filters_codes_outside_validity(): void
    {
        $now = Carbon::parse('2026-06-01 12:00:00');
        Carbon::setTestNow($now);

        PromoCode::query()->create([
            'code' => 'FUTURE',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10.00,
            'starts_at' => $now->copy()->addDay(),
            'is_active' => true,
        ]);
        PromoCode::query()->create([
            'code' => 'EXPIRED',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10.00,
            'ends_at' => $now->copy()->subDay(),
            'is_active' => true,
        ]);
        PromoCode::query()->create([
            'code' => 'CURRENT',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10.00,
            'starts_at' => $now->copy()->subDay(),
            'ends_at' => $now->copy()->addDay(),
            'is_active' => true,
        ]);

        $this->assertNull(PromoCodeFacade::discountFor('FUTURE', 100.00));
        $this->assertNull(PromoCodeFacade::discountFor('EXPIRED', 100.00));
        $this->assertSame(10.0, PromoCodeFacade::discountFor('CURRENT', 100.00));

        Carbon::setTestNow();
    }

    public function test_unknown_code_returns_null(): void
    {
        $this->assertNull(PromoCodeFacade::discountFor('NOPE', 100.00));
        $this->assertFalse(PromoCodeFacade::exists('NOPE'));
    }
}
