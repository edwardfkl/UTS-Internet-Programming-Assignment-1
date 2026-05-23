<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * API contract when a browser cart token points at a missing order (e.g. admin deleted all orders).
 * Frontend recovers by POST /api/cart/sessions; these tests document backend behaviour.
 */
class DeletedCartOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_show_returns_404_for_unknown_token(): void
    {
        $this->withHeader('X-Cart-Token', (string) Str::uuid())
            ->getJson('/api/cart')
            ->assertNotFound();
    }

    public function test_cart_show_returns_404_after_order_row_deleted(): void
    {
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        $token = $order->token;

        $order->delete();

        $this->withHeader('X-Cart-Token', $token)
            ->getJson('/api/cart')
            ->assertNotFound();
    }

    public function test_add_item_returns_404_for_unknown_token(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->withHeader('X-Cart-Token', (string) Str::uuid())
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertNotFound();
    }

    public function test_add_item_returns_404_after_order_row_deleted(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        $token = $order->token;
        $order->delete();

        $this->withHeader('X-Cart-Token', $token)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertNotFound();
    }

    public function test_update_item_returns_404_after_order_row_deleted(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);
        $token = $order->token;
        $itemId = $item->id;
        $order->delete();

        $this->withHeader('X-Cart-Token', $token)
            ->patchJson("/api/cart/items/{$itemId}", ['quantity' => 2])
            ->assertNotFound();
    }

    public function test_delete_item_returns_404_after_order_row_deleted(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);
        $token = $order->token;
        $itemId = $item->id;
        $order->delete();

        $this->withHeader('X-Cart-Token', $token)
            ->deleteJson("/api/cart/items/{$itemId}")
            ->assertNotFound();
    }

    public function test_guest_cart_session_creates_new_draft_order(): void
    {
        $response = $this->postJson('/api/cart/sessions')->assertCreated();

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        $this->assertDatabaseHas('orders', [
            'token' => $token,
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);

        $this->withHeader('X-Cart-Token', $token)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_CART)
            ->assertJsonPath('total', 0);
    }

    public function test_new_session_after_deletion_allows_adding_items_again(): void
    {
        $product = Product::factory()->create(['price' => 10, 'stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        $order->delete();

        $newToken = $this->postJson('/api/cart/sessions')
            ->assertCreated()
            ->json('token');

        $this->withHeader('X-Cart-Token', $newToken)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertCreated();

        $this->withHeader('X-Cart-Token', $newToken)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('total', 20);
    }

    public function test_checkout_promotes_same_order_row_to_pending_payment_without_deleting(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 15, 'stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 15,
        ]);
        $orderId = $order->id;

        $this->withToken($this->jwtTokenFor($user))
            ->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/checkout', [
                'payment_method' => 'atm_transfer',
                'shipping_recipient_name' => 'Buyer',
                'shipping_phone' => '0400000000',
                'shipping_line1' => '1 St',
                'shipping_line2' => null,
                'shipping_city' => 'Sydney',
                'shipping_state' => 'NSW',
                'shipping_postcode' => '2000',
                'shipping_country' => 'Australia',
            ])
            ->assertCreated()
            ->assertJsonPath('status', Order::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('order_token', $order->token);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_submitted_order_still_resolves_by_token_but_cart_api_is_immutable(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'placed_at' => now(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        $this->withHeader('X-Cart-Token', $order->token)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_PENDING_PAYMENT);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertStatus(409);
    }
}
