<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_city_id')->nullable()->after('user_id')->constrained('shipping_cities')->nullOnDelete();
            $table->string('shipping_city_name')->nullable()->after('delivery_address');
            $table->decimal('subtotal', 10, 2)->default(0)->after('status');
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_city_id');
            $table->dropColumn(['shipping_city_name', 'subtotal', 'shipping_cost']);
        });
    }
};
