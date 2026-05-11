<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

class ProductStatusFlagsTest extends TestCase
{
    public function test_status_constants_match_known_values(): void
    {
        $this->assertSame('active', Product::STATUS_ACTIVE);
        $this->assertSame('inactive', Product::STATUS_INACTIVE);
        $this->assertSame('draft', Product::STATUS_DRAFT);
    }

    public function test_statuses_list_contains_all_three_states(): void
    {
        $this->assertSame(
            [Product::STATUS_ACTIVE, Product::STATUS_INACTIVE, Product::STATUS_DRAFT],
            Product::STATUSES,
        );
    }

    public function test_is_active_only_true_when_status_active(): void
    {
        $product = new Product();

        $product->status = Product::STATUS_ACTIVE;
        $this->assertTrue($product->isActive());

        $product->status = Product::STATUS_INACTIVE;
        $this->assertFalse($product->isActive());

        $product->status = Product::STATUS_DRAFT;
        $this->assertFalse($product->isActive());
    }

    public function test_scope_listed_appends_active_status_filter(): void
    {
        $query = Product::query()->listed();
        $wheres = $query->getQuery()->wheres;

        $this->assertCount(1, $wheres);
        $this->assertSame('status', $wheres[0]['column']);
        $this->assertSame(Product::STATUS_ACTIVE, $wheres[0]['value']);
    }
}
