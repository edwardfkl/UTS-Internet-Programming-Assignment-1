<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_create_paid_order_deducts_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 20.00]);

        $this->actingAs($admin)
            ->post(route('admin.orders.store'), [
                'status' => Order::STATUS_PAID,
                'payment_method' => 'pay_id',
                'user_id' => $customer->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
                'shipping_recipient_name' => 'Alice',
                'shipping_line1' => '1 St',
                'shipping_city' => 'Sydney',
                'shipping_postcode' => '2000',
                'shipping_country' => 'AU',
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertSame(7, $product->stock);

        $order = Order::query()->latest('id')->first();
        $this->assertTrue($order->stock_reserved);
        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertSame('60.00', $order->subtotal_amount);
    }

    public function test_admin_create_cart_status_does_not_deduct_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->actingAs($admin)
            ->post(route('admin.orders.store'), [
                'status' => Order::STATUS_CART,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertFalse(Order::query()->latest('id')->first()->stock_reserved);
    }

    public function test_admin_create_rejects_insufficient_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 1]);

        $this->actingAs($admin)
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), [
                'status' => Order::STATUS_PENDING_PAYMENT,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5],
                ],
            ])
            ->assertRedirect(route('admin.orders.create'))
            ->assertSessionHasErrors(['items']);

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cancelling_reserved_order_restores_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 8]);

        $this->actingAs($admin)
            ->post(route('admin.orders.store'), [
                'status' => Order::STATUS_PAID,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $order = Order::query()->latest('id')->first();
        $this->assertSame(6, $product->fresh()->stock);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'status' => Order::STATUS_CANCELLED,
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertFalse($order->stock_reserved);
        $this->assertSame(8, $product->fresh()->stock);
    }
}
