<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartCheckoutEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_show_requires_token(): void
    {
        $this->getJson('/api/cart')->assertUnauthorized();
    }

    public function test_cart_store_item_rejects_quantity_over_stock(): void
    {
        $product = Product::factory()->create(['stock' => 2]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_cart_store_item_rejects_accumulated_quantity_over_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 4,
            ])
            ->assertCreated();

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_cart_update_item_rejects_quantity_over_stock(): void
    {
        $product = Product::factory()->create(['stock' => 3]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->price,
        ]);

        $this->withHeader('X-Cart-Token', $order->token)
            ->patchJson("/api/cart/items/{$item->id}", ['quantity' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_cart_cannot_mutate_submitted_order(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertStatus(409);
    }

    private function checkoutPayload(): array
    {
        return [
            'payment_method' => 'bpay',
            'shipping_recipient_name' => 'R',
            'shipping_phone' => '0400000000',
            'shipping_line1' => '1 St',
            'shipping_line2' => null,
            'shipping_city' => 'Sydney',
            'shipping_state' => 'NSW',
            'shipping_postcode' => '2000',
            'shipping_country' => 'AU',
        ];
    }

    public function test_checkout_requires_cart_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', $this->checkoutPayload())
            ->assertUnauthorized();
    }

    public function test_checkout_rejects_empty_cart(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/checkout', $this->checkoutPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);
    }

    public function test_checkout_rejects_already_submitted_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/checkout', $this->checkoutPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);
    }

    public function test_checkout_rejects_cart_owned_by_another_user(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => Order::STATUS_CART,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        Sanctum::actingAs($intruder);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/checkout', $this->checkoutPayload())
            ->assertForbidden();
    }
}
