<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoCodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_endpoint_returns_discount_for_valid_code(): void
    {
        $user = User::factory()->create();
        PromoCode::query()->create([
            'code' => 'WELCOME10',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10.00,
            'is_active' => true,
        ]);

        $this->withToken($this->jwtTokenFor($user))
            ->postJson('/api/promo-codes/preview', [
                'code' => 'welcome10',
                'subtotal' => 80.00,
            ])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('code', 'WELCOME10')
            ->assertJsonPath('discount', 10)
            ->assertJsonPath('total', 70);
    }

    public function test_preview_endpoint_rejects_invalid_code(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->jwtTokenFor($user))
            ->postJson('/api/promo-codes/preview', [
                'code' => 'NOPE',
                'subtotal' => 100.00,
            ])
            ->assertStatus(422)
            ->assertJsonPath('valid', false)
            ->assertJsonPath('discount', 0);
    }

    public function test_preview_endpoint_rejects_code_below_min_subtotal(): void
    {
        $user = User::factory()->create();
        PromoCode::query()->create([
            'code' => 'OVER100',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 20.00,
            'min_subtotal' => 100.00,
            'is_active' => true,
        ]);

        $this->withToken($this->jwtTokenFor($user))
            ->postJson('/api/promo-codes/preview', [
                'code' => 'OVER100',
                'subtotal' => 50.00,
            ])
            ->assertStatus(422)
            ->assertJsonPath('valid', false);
    }

    public function test_preview_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/promo-codes/preview', [
            'code' => 'WELCOME10',
            'subtotal' => 100.00,
        ])->assertStatus(401);
    }

    public function test_checkout_applies_promo_code_to_totals(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 30.00, 'stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 30.00,
        ]);

        PromoCode::query()->create([
            'code' => 'WELCOME10',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10.00,
            'is_active' => true,
        ]);

        $this->withToken($this->jwtTokenFor($user))
            ->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/checkout', [
                'payment_method' => 'pay_id',
                'promo_code' => 'welcome10',
                'shipping_recipient_name' => 'Test',
                'shipping_phone' => '0400',
                'shipping_line1' => '1 St',
                'shipping_city' => 'Sydney',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2000',
                'shipping_country' => 'Australia',
            ])
            ->assertCreated()
            ->assertJsonPath('promo_code', 'WELCOME10')
            ->assertJsonPath('subtotal_amount', 60)
            ->assertJsonPath('discount_amount', 10)
            ->assertJsonPath('total_amount', 50);
    }

    public function test_checkout_rejects_invalid_promo_code(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 30.00, 'stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 30.00,
        ]);

        $this->withToken($this->jwtTokenFor($user))
            ->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/checkout', [
                'payment_method' => 'pay_id',
                'promo_code' => 'NOPE',
                'shipping_recipient_name' => 'Test',
                'shipping_phone' => '0400',
                'shipping_line1' => '1 St',
                'shipping_city' => 'Sydney',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2000',
                'shipping_country' => 'Australia',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('promo_code');
    }
}
