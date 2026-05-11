<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_product_index_only_returns_active_products(): void
    {
        Product::factory()->create(['name' => 'Visible item']);
        Product::factory()->inactive()->create(['name' => 'Hidden item']);
        Product::factory()->draft()->create(['name' => 'Draft item']);

        $response = $this->getJson('/api/products')->assertOk();

        $names = collect($response->json())->pluck('name')->all();
        $this->assertContains('Visible item', $names);
        $this->assertNotContains('Hidden item', $names);
        $this->assertNotContains('Draft item', $names);
    }

    public function test_api_product_show_returns_404_for_inactive_product(): void
    {
        $hidden = Product::factory()->inactive()->create();

        $this->getJson('/api/products/'.$hidden->id)
            ->assertNotFound();
    }

    public function test_cart_store_item_rejects_inactive_product(): void
    {
        $product = Product::factory()->inactive()->create(['stock' => 5]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    public function test_cart_update_item_rejects_when_product_becomes_inactive(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_CART,
        ]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);

        $product->status = Product::STATUS_INACTIVE;
        $product->save();

        $this->withToken($this->jwtTokenFor($user))
            ->withHeader('X-Cart-Token', $order->token)
            ->patchJson('/api/cart/items/'.$item->id, ['quantity' => 2])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    public function test_checkout_rejects_order_with_inactive_line_item(): void
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

        $product->status = Product::STATUS_DRAFT;
        $product->save();

        $this->withToken($this->jwtTokenFor($user))
            ->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/checkout', [
                'payment_method' => 'pay_id',
                'shipping_recipient_name' => 'Test',
                'shipping_phone' => '0400',
                'shipping_line1' => '1 St',
                'shipping_city' => 'Sydney',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2000',
                'shipping_country' => 'Australia',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('order');
    }
}
