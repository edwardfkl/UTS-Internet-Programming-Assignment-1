<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Support\OrderStock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderStockStatusTest extends TestCase
{
    #[DataProvider('nonReservingStatusesProvider')]
    public function test_cart_and_cancelled_do_not_reserve_stock(string $status): void
    {
        $this->assertFalse(OrderStock::statusReservesStock($status));
    }

    public static function nonReservingStatusesProvider(): array
    {
        return [
            'draft cart' => [Order::STATUS_CART],
            'cancelled' => [Order::STATUS_CANCELLED],
        ];
    }

    #[DataProvider('reservingStatusesProvider')]
    public function test_fulfilment_statuses_reserve_stock(string $status): void
    {
        $this->assertTrue(OrderStock::statusReservesStock($status));
    }

    public static function reservingStatusesProvider(): array
    {
        return [
            'pending payment' => [Order::STATUS_PENDING_PAYMENT],
            'paid' => [Order::STATUS_PAID],
            'shipped' => [Order::STATUS_SHIPPED],
            'completed' => [Order::STATUS_COMPLETED],
        ];
    }
}
