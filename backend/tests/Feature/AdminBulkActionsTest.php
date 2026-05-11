<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_users_bulk_set_status_suspends_selected_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.bulk'), [
            'action' => 'set_status',
            'status' => User::STATUS_SUSPENDED,
            'ids' => [$u1->id, $u2->id],
        ])->assertRedirect();

        $this->assertSame(User::STATUS_SUSPENDED, $u1->fresh()->status);
        $this->assertSame(User::STATUS_SUSPENDED, $u2->fresh()->status);
        $this->assertSame(User::STATUS_ACTIVE, $other->fresh()->status);
    }

    public function test_users_bulk_delete_skips_self_and_last_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $victim = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.bulk'), [
            'action' => 'delete',
            'ids' => [$admin->id, $victim->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $victim->id]);
    }

    public function test_products_bulk_set_status_changes_listing_state(): void
    {
        $admin = User::factory()->admin()->create();
        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.bulk'), [
            'action' => 'set_status',
            'status' => Product::STATUS_DRAFT,
            'ids' => [$p1->id, $p2->id],
        ])->assertRedirect();

        $this->assertSame(Product::STATUS_DRAFT, $p1->fresh()->status);
        $this->assertSame(Product::STATUS_DRAFT, $p2->fresh()->status);
    }

    public function test_products_bulk_delete_skips_products_on_placed_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $orphan = Product::factory()->create();
        $sold = Product::factory()->create();

        $order = Order::factory()->create(['status' => Order::STATUS_PENDING_PAYMENT]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $sold->id,
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        $this->actingAs($admin)->post(route('admin.products.bulk'), [
            'action' => 'delete',
            'ids' => [$orphan->id, $sold->id],
        ])->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $orphan->id]);
        $this->assertDatabaseHas('products', ['id' => $sold->id]);
    }

    public function test_orders_bulk_set_status_marks_orders_shipped(): void
    {
        $admin = User::factory()->admin()->create();
        $o1 = Order::factory()->create(['status' => Order::STATUS_PAID]);
        $o2 = Order::factory()->create(['status' => Order::STATUS_PAID]);

        $this->actingAs($admin)->post(route('admin.orders.bulk'), [
            'action' => 'set_status',
            'status' => Order::STATUS_SHIPPED,
            'ids' => [$o1->id, $o2->id],
        ])->assertRedirect();

        $this->assertSame(Order::STATUS_SHIPPED, $o1->fresh()->status);
        $this->assertSame(Order::STATUS_SHIPPED, $o2->fresh()->status);
    }

    public function test_promo_codes_bulk_deactivate_disables_selected_codes(): void
    {
        $admin = User::factory()->admin()->create();
        $live = PromoCode::query()->create([
            'code' => 'KEEP',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 5,
            'is_active' => true,
        ]);
        $target = PromoCode::query()->create([
            'code' => 'GONE',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.promo-codes.bulk'), [
            'action' => 'set_status',
            'status' => 'inactive',
            'ids' => [$target->id],
        ])->assertRedirect();

        $this->assertTrue($live->fresh()->is_active);
        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_non_admin_cannot_invoke_bulk_endpoint(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('admin.products.bulk'), [
            'action' => 'delete',
            'ids' => [$product->id],
        ])->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
