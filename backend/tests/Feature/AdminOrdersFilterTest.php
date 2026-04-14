<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_orders_index_can_filter_to_draft_carts_only(): void
    {
        $admin = User::factory()->admin()->create();
        $cart = Order::factory()->create(['status' => Order::STATUS_CART]);
        $pending = Order::factory()->create(['status' => Order::STATUS_PENDING_PAYMENT]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['status' => Order::STATUS_CART]))
            ->assertOk()
            ->assertSee($cart->token, false)
            ->assertDontSee($pending->token, false);
    }
}
