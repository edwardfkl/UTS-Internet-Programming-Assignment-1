<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 500),
            'image_url' => null,
            'stock' => fake()->numberBetween(0, 100),
            'status' => Product::STATUS_ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Product::STATUS_INACTIVE]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Product::STATUS_DRAFT]);
    }
}
