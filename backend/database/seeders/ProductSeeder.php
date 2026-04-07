<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Wired mechanical keyboard',
                'description' => 'Clicky switches, aluminium case — solid for work and gaming.',
                'price' => 899.00,
                'image_url' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600&q=80',
                'stock' => 24,
            ],
            [
                'name' => 'Wireless mouse',
                'description' => 'Quiet clicks, multi-device switching, months of battery life.',
                'price' => 249.50,
                'image_url' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=600&q=80',
                'stock' => 60,
            ],
            [
                'name' => 'USB-C cable (2 m)',
                'description' => '100 W fast charge, braided jacket, strain-relief tested.',
                'price' => 79.00,
                'image_url' => 'https://images.unsplash.com/photo-1583863788434-e93a89ee3507?w=600&q=80',
                'stock' => 120,
            ],
            [
                'name' => 'Bluetooth headphones',
                'description' => 'ANC, transparency mode, IPX4 sweat resistance.',
                'price' => 1299.00,
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&q=80',
                'stock' => 15,
            ],
            [
                'name' => 'Monitor arm',
                'description' => 'Fits 17–32 inch displays, gas spring height, built-in cable channel.',
                'price' => 459.00,
                'image_url' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=600&q=80',
                'stock' => 8,
            ],
        ];

        foreach ($rows as $row) {
            Product::query()->updateOrCreate(
                ['name' => $row['name']],
                $row
            );
        }
    }
}
