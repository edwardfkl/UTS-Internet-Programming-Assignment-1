<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_can_see_order_show_page(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Mixer']);
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => Order::STATUS_PAID,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'unit_price' => '50.00',
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Mixer', false);
    }

    public function test_admin_can_update_status_and_shipping_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'status' => Order::STATUS_PENDING_PAYMENT,
            'shipping_recipient_name' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'status' => Order::STATUS_SHIPPED,
                'payment_method' => 'pay_id',
                'shipping_recipient_name' => 'Alice',
                'shipping_line1' => '1 Demo St',
                'shipping_city' => 'Sydney',
                'shipping_postcode' => '2000',
                'shipping_country' => 'AU',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame(Order::STATUS_SHIPPED, $order->status);
        $this->assertSame('pay_id', $order->payment_method);
        $this->assertSame('Alice', $order->shipping_recipient_name);
    }

    public function test_update_rejects_unknown_status(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['status' => Order::STATUS_PAID]);

        $this->actingAs($admin)
            ->from(route('admin.orders.edit', $order))
            ->put(route('admin.orders.update', $order), [
                'status' => 'mystery',
            ])
            ->assertRedirect(route('admin.orders.edit', $order))
            ->assertSessionHasErrors(['status']);

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
    }

    public function test_update_accepts_blank_strings_for_optional_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'status' => Order::STATUS_PAID,
            'placed_at' => now(),
            'user_id' => User::factory()->create()->id,
            'shipping_recipient_name' => 'Old Name',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'status' => Order::STATUS_PAID,
                'user_id' => '',
                'placed_at' => '',
                'shipping_recipient_name' => '',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertNull($order->user_id);
        $this->assertNull($order->placed_at);
        $this->assertNull($order->shipping_recipient_name);
    }

    public function test_admin_can_delete_an_order_and_its_items(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create(['status' => Order::STATUS_PAID]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.orders.destroy', $order))
            ->assertRedirect(route('admin.orders.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);
    }

    public function test_index_search_supports_token_lookup(): void
    {
        $admin = User::factory()->admin()->create();
        $target = Order::factory()->create([
            'token' => 'aaaa-aaaa-aaaa-aaaa',
            'status' => Order::STATUS_PAID,
        ]);
        $other = Order::factory()->create([
            'token' => 'bbbb-bbbb-bbbb-bbbb',
            'status' => Order::STATUS_PAID,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.orders.index', ['q' => 'aaaa']))
            ->assertOk();

        $response->assertSee($target->token, false);
        $response->assertDontSee($other->token, false);
    }

    public function test_non_admin_cannot_update_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['status' => Order::STATUS_PAID]);

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), [
                'status' => Order::STATUS_SHIPPED,
            ])
            ->assertStatus(403);

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
    }
}
