<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLocaleAndProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_show_returns_null_without_cookie(): void
    {
        $this->getJson('/api/locale')
            ->assertOk()
            ->assertExactJson(['locale' => null]);
    }

    public function test_locale_store_sets_cookie_and_returns_locale(): void
    {
        $this->postJson('/api/locale', ['locale' => 'ja'])
            ->assertOk()
            ->assertJson(['locale' => 'ja'])
            ->assertCookie('admin_locale', 'ja');
    }

    public function test_locale_store_validates_allowed_values(): void
    {
        $this->postJson('/api/locale', ['locale' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_products_index_is_ordered_by_name(): void
    {
        Product::factory()->create(['name' => 'Zebra Kit']);
        Product::factory()->create(['name' => 'Alpha Tool']);

        $response = $this->getJson('/api/products')->assertOk();
        $names = collect($response->json())->pluck('name')->all();

        $this->assertSame(['Alpha Tool', 'Zebra Kit'], $names);
    }

    public function test_products_index_filters_by_query_parameter(): void
    {
        Product::factory()->create(['name' => 'Acme Glue', 'description' => 'Strong bond']);
        Product::factory()->create(['name' => 'Beta Tape', 'description' => 'Sealing']);

        $this->getJson('/api/products?q=glue')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Acme Glue');

        $this->getJson('/api/products?q=seal')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Beta Tape');
    }

    public function test_products_index_rejects_overlong_search_query(): void
    {
        $this->getJson('/api/products?q='.str_repeat('a', 121))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    public function test_product_show_returns_fields(): void
    {
        $product = Product::factory()->create([
            'name' => 'One Off',
            'price' => 19.99,
            'stock' => 4,
        ]);

        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('name', 'One Off')
            ->assertJsonPath('stock', 4);
    }
}
