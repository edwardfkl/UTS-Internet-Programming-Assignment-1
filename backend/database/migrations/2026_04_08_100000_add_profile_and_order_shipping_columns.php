<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 64)->nullable()->after('password');
            $table->string('shipping_recipient_name', 255)->nullable()->after('phone');
            $table->string('shipping_line1', 255)->nullable()->after('shipping_recipient_name');
            $table->string('shipping_line2', 255)->nullable()->after('shipping_line1');
            $table->string('shipping_city', 120)->nullable()->after('shipping_line2');
            $table->string('shipping_state', 80)->nullable()->after('shipping_city');
            $table->string('shipping_postcode', 32)->nullable()->after('shipping_state');
            $table->string('shipping_country', 120)->nullable()->default('Australia')->after('shipping_postcode');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_recipient_name', 255)->nullable()->after('placed_at');
            $table->string('shipping_phone', 64)->nullable()->after('shipping_recipient_name');
            $table->string('shipping_line1', 255)->nullable()->after('shipping_phone');
            $table->string('shipping_line2', 255)->nullable()->after('shipping_line1');
            $table->string('shipping_city', 120)->nullable()->after('shipping_line2');
            $table->string('shipping_state', 80)->nullable()->after('shipping_city');
            $table->string('shipping_postcode', 32)->nullable()->after('shipping_state');
            $table->string('shipping_country', 120)->nullable()->after('shipping_postcode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'shipping_recipient_name',
                'shipping_line1',
                'shipping_line2',
                'shipping_city',
                'shipping_state',
                'shipping_postcode',
                'shipping_country',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_recipient_name',
                'shipping_phone',
                'shipping_line1',
                'shipping_line2',
                'shipping_city',
                'shipping_state',
                'shipping_postcode',
                'shipping_country',
            ]);
        });
    }
};
