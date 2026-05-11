<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('promo_code', 32)->nullable()->after('payment_method');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('promo_code');
            $table->decimal('subtotal_amount', 10, 2)->default(0)->after('discount_amount');
            $table->decimal('total_amount', 10, 2)->default(0)->after('subtotal_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'promo_code',
                'discount_amount',
                'subtotal_amount',
                'total_amount',
            ]);
        });
    }
};
