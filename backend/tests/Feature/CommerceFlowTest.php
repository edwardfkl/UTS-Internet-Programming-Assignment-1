<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_require_admin_privilege(): void
    {
        $guestResponse = $this->get('/admin/users');
        $guestResponse->assertRedirect(route('admin.login'));

        $normalUser = User::factory()->create();
        $this->actingAs($normalUser)->get('/admin/users')->assertForbidden();

        $adminUser = User::factory()->admin()->create();
        $this->actingAs($adminUser)->get('/admin/users')->assertOk();
    }

    public function test_cart_attach_links_guest_cart_to_authenticated_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/cart/attach')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_cart_attach_rejects_another_users_cart(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => Order::STATUS_CART,
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/cart/attach')
            ->assertForbidden();
    }

    public function test_checkout_submits_cart_and_sets_shipping_details(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 25.50, 'stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 25.50,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'payment_method' => 'pay_id',
            'shipping_recipient_name' => 'Test Recipient',
            'shipping_phone' => '0400000000',
            'shipping_line1' => '1 Test Street',
            'shipping_line2' => null,
            'shipping_city' => 'Sydney',
            'shipping_state' => 'NSW',
            'shipping_postcode' => '2000',
            'shipping_country' => 'Australia',
            'save_to_profile' => true,
        ];

        $this->withHeader('X-Cart-Token', $order->token)
            ->postJson('/api/checkout', $payload)
            ->assertCreated()
            ->assertJsonPath('status', Order::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('payment_method', 'pay_id')
            ->assertJsonPath('total', 51)
            ->assertJsonPath('shipping.recipient_name', 'Test Recipient');

        $order->refresh();
        $user->refresh();

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertSame($user->id, $order->user_id);
        $this->assertNotNull($order->placed_at);
        $this->assertSame('Test Recipient', $user->shipping_recipient_name);
    }
}
