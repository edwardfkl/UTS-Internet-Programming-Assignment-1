<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ApiCartAccessAndLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_user_bound_cart(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => Order::STATUS_CART,
        ]);

        $this->withHeader('X-Cart-Token', $order->token)
            ->getJson('/api/cart')
            ->assertForbidden();
    }

    public function test_authenticated_user_can_read_own_cart(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_CART,
        ]);
        $plain = $user->createToken('cart-read')->plainTextToken;

        $this->withToken($plain)
            ->withHeader('X-Cart-Token', $order->token)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_CART);
    }

    public function test_logout_invalidates_bearer_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertSame(0, $user->fresh()->tokens()->count());

        // Same PHP process: Sanctum may leave the resolved user on the auth manager between HTTP calls.
        Auth::forgetGuards();
        $this->flushSession();
        $this->flushHeaders();

        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_delete_cart_item_removes_line(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => null,
            'status' => Order::STATUS_CART,
        ]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        $this->withHeader('X-Cart-Token', $order->token)
            ->deleteJson('/api/cart/items/'.$item->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);
    }

    public function test_new_cart_session_optionally_sets_user_id_when_bearer_present(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/cart/sessions')->assertCreated();

        $newToken = $response->json('token');
        $this->assertNotEmpty($newToken);

        $this->assertDatabaseHas('orders', [
            'token' => $newToken,
            'user_id' => $user->id,
            'status' => Order::STATUS_CART,
        ]);
    }
}
