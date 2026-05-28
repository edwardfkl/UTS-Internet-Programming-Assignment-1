<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'code' => 'WELCOME10',
                'label' => 'Welcome — A$10 off',
                'type' => 'fixed',
                'amount' => 10.00,
                'min_subtotal' => null,
                'is_active' => true,
            ],
            [
                'code' => 'SAVE20',
                'label' => 'A$20 off your order',
                'type' => 'fixed',
                'amount' => 20.00,
                'min_subtotal' => 100.00,
                'is_active' => true,
            ],
        ];

        foreach ($rows as $row) {
            PromoCode::query()->updateOrCreate(
                ['code' => $row['code']],
                $row,
            );
        }
    }
}
