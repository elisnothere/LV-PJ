<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_address_id')->nullable()->after('shipping_city_id')->constrained('user_addresses')->nullOnDelete();
            $table->string('delivery_address_line_1')->nullable()->after('delivery_address');
            $table->string('delivery_address_line_2')->nullable()->after('delivery_address_line_1');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_address_id');
            $table->dropColumn(['delivery_address_line_1', 'delivery_address_line_2']);
        });
    }
};
