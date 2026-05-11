<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOrdersAndReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_index_returns_only_placed_orders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_CART,
        ]);
        $paidOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PAID,
            'subtotal_amount' => 60.00,
            'discount_amount' => 10.00,
            'total_amount' => 50.00,
        ]);
        Order::factory()->create([
            'user_id' => $other->id,
            'status' => Order::STATUS_PAID,
        ]);

        $response = $this->withToken($this->jwtTokenFor($user))
            ->getJson('/api/orders')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$paidOrder->id], $ids);

        $response->assertJsonPath('data.0.total_amount', 50);
        $response->assertJsonPath('data.0.reference', 'SSP-'.str_pad((string) $paidOrder->id, 6, '0', STR_PAD_LEFT));
    }

    public function test_orders_show_blocks_other_users_orders(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $stranger->id,
            'status' => Order::STATUS_PAID,
        ]);

        $this->withToken($this->jwtTokenFor($user))
            ->getJson('/api/orders/'.$order->id)
            ->assertForbidden();
    }

    public function test_review_store_creates_and_updates_existing_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->withToken($this->jwtTokenFor($user))
            ->postJson('/api/products/'.$product->id.'/reviews', [
                'rating' => 4,
                'comment' => 'Pretty good',
            ])
            ->assertCreated()
            ->assertJsonPath('review.rating', 4)
            ->assertJsonPath('average_rating', 4)
            ->assertJsonPath('review_count', 1);

        $this->withToken($this->jwtTokenFor($user))
            ->postJson('/api/products/'.$product->id.'/reviews', [
                'rating' => 5,
                'comment' => 'Actually amazing',
            ])
            ->assertCreated()
            ->assertJsonPath('review.rating', 5)
            ->assertJsonPath('average_rating', 5)
            ->assertJsonPath('review_count', 1);

        $this->assertSame(1, Review::query()->where('product_id', $product->id)->count());
    }

    public function test_review_index_is_public_and_returns_average(): void
    {
        $product = Product::factory()->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        Review::query()->create([
            'user_id' => $u1->id,
            'product_id' => $product->id,
            'rating' => 4,
        ]);
        Review::query()->create([
            'user_id' => $u2->id,
            'product_id' => $product->id,
            'rating' => 2,
        ]);

        $this->getJson('/api/products/'.$product->id.'/reviews')
            ->assertOk()
            ->assertJsonPath('review_count', 2)
            ->assertJsonPath('average_rating', 3);
    }

    public function test_review_store_requires_authentication(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/api/products/'.$product->id.'/reviews', [
            'rating' => 5,
        ])->assertStatus(401);
    }

    public function test_product_show_includes_average_rating_and_review_count(): void
    {
        $product = Product::factory()->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        Review::query()->create([
            'user_id' => $u1->id,
            'product_id' => $product->id,
            'rating' => 5,
        ]);
        Review::query()->create([
            'user_id' => $u2->id,
            'product_id' => $product->id,
            'rating' => 3,
        ]);

        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('average_rating', 4)
            ->assertJsonPath('review_count', 2);
    }

    /**
     * Pin existing order seeded by OrderItem factory cleans up after itself.
     * We just need a placed order with at least one line for show().
     */
    public function test_orders_show_returns_line_items_for_owner(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PAID,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 12.50,
        ]);

        $this->withToken($this->jwtTokenFor($user))
            ->getJson('/api/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('lines.0.quantity', 3)
            ->assertJsonPath('lines.0.line_total', 37.5);
    }
}
