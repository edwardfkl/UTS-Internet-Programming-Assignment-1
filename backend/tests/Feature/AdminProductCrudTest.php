<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_guest_cannot_reach_product_index(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_forbidden_from_admin_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_view_create_form(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('name', false);
    }

    public function test_admin_can_create_a_product_with_valid_data(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = [
            'name' => 'Studio Mic',
            'description' => 'Cardioid condenser',
            'price' => '199.95',
            'image_url' => 'https://example.com/mic.png',
            'stock' => 12,
            'status' => Product::STATUS_ACTIVE,
        ];

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Studio Mic',
            'status' => Product::STATUS_ACTIVE,
            'stock' => 12,
        ]);
    }

    public function test_store_rejects_negative_price_and_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => '',
                'price' => '-1',
                'stock' => -5,
                'status' => 'unknown',
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors(['name', 'price', 'stock', 'status']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_store_rejects_too_long_image_url(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'X',
                'price' => '1.00',
                'stock' => 0,
                'status' => Product::STATUS_DRAFT,
                'image_url' => str_repeat('a', 2049),
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors(['image_url']);
    }

    public function test_admin_can_update_a_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'name' => 'Old name',
            'price' => '10.00',
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'name' => 'New name',
                'description' => 'Updated description',
                'price' => '15.50',
                'image_url' => '',
                'stock' => 8,
                'status' => Product::STATUS_INACTIVE,
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('New name', $product->name);
        $this->assertSame('Updated description', $product->description);
        $this->assertSame(8, $product->stock);
        $this->assertSame(Product::STATUS_INACTIVE, $product->status);
        $this->assertNull($product->image_url);
    }

    public function test_admin_can_delete_an_unreferenced_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_admin_cannot_delete_a_product_on_placed_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create(['status' => Order::STATUS_PAID]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.products.index'))
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasErrors(['delete']);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_index_filter_and_search_returns_only_matching_rows(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['name' => 'Cable XLR', 'status' => Product::STATUS_ACTIVE]);
        Product::factory()->create(['name' => 'Headphones', 'status' => Product::STATUS_INACTIVE]);
        Product::factory()->create(['name' => 'Cable Patch', 'status' => Product::STATUS_DRAFT]);

        $response = $this->actingAs($admin)
            ->get(route('admin.products.index', ['q' => 'Cable', 'status' => Product::STATUS_DRAFT]))
            ->assertOk();

        $response->assertSee('Cable Patch', false);
        $response->assertDontSee('Cable XLR', false);
        $response->assertDontSee('Headphones', false);
    }
}
