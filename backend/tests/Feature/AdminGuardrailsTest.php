<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_cannot_remove_last_admin_flag(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Sole Admin',
            'email' => 'sole@example.com',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => 'Sole Admin',
            'email' => 'sole@example.com',
            'avatar_url' => null,
            'phone' => null,
            'shipping_recipient_name' => null,
            'shipping_line1' => null,
            'shipping_line2' => null,
            'shipping_city' => null,
            'shipping_state' => null,
            'shipping_postcode' => null,
            'shipping_country' => null,
        ]);

        $response->assertSessionHasErrors('is_admin');

        $admin->refresh();
        $this->assertTrue($admin->is_admin);
    }

    public function test_cannot_delete_product_referenced_on_placed_order(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create([
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_non_admin_blocked_from_admin_login_flow_after_password_ok(): void
    {
        $user = User::factory()->create([
            'email' => 'shopper@example.com',
            'password' => 'password12',
            'is_admin' => false,
        ]);

        $this->from(route('admin.login'))->post(route('admin.login'), [
            'email' => 'shopper@example.com',
            'password' => 'password12',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
