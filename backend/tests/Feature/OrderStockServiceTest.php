<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\OrderStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderStockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserve_deducts_stock_and_marks_order(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create([
            'status' => Order::STATUS_CART,
            'stock_reserved' => false,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 20,
        ]);

        app(OrderStock::class)->reserve($order->fresh());

        $product->refresh();
        $order->refresh();
        $this->assertSame(7, $product->stock);
        $this->assertTrue($order->stock_reserved);
    }

    public function test_reserve_is_idempotent_when_already_reserved(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create(['stock_reserved' => true]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 15,
        ]);

        app(OrderStock::class)->reserve($order->fresh());

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_reserve_rejects_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock' => 1, 'name' => 'Mouse']);
        $order = Order::factory()->create(['stock_reserved' => false]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 10,
        ]);

        $this->expectException(ValidationException::class);
        app(OrderStock::class)->reserve($order->fresh());
    }

    public function test_release_restores_stock_and_clears_flag(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create(['stock_reserved' => true]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'unit_price' => 12,
        ]);

        $product->decrement('stock', 4);

        app(OrderStock::class)->release($order->fresh());

        $order->refresh();
        $product->refresh();
        $this->assertSame(10, $product->stock);
        $this->assertFalse($order->stock_reserved);
    }

    public function test_sync_for_status_change_reserves_when_leaving_cart(): void
    {
        $product = Product::factory()->create(['stock' => 8]);
        $order = Order::factory()->create([
            'status' => Order::STATUS_CART,
            'stock_reserved' => false,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 30,
        ]);

        app(OrderStock::class)->syncForStatusChange(
            $order->fresh(),
            Order::STATUS_CART,
            Order::STATUS_PENDING_PAYMENT,
        );

        $this->assertSame(6, $product->fresh()->stock);
        $this->assertTrue($order->fresh()->stock_reserved);
    }

    public function test_sync_for_status_change_releases_when_cancelling(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $order = Order::factory()->create([
            'status' => Order::STATUS_PAID,
            'stock_reserved' => true,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 18,
        ]);
        $product->decrement('stock', 2);

        app(OrderStock::class)->syncForStatusChange(
            $order->fresh(),
            Order::STATUS_PAID,
            Order::STATUS_CANCELLED,
        );

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertFalse($order->fresh()->stock_reserved);
    }
}
