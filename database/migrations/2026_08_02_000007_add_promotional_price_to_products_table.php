<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('promotional_price', 10, 2)->nullable()->after('price');
            $table->dateTime('promotional_starts_at')->nullable()->after('promotional_price');
            $table->dateTime('promotional_ends_at')->nullable()->after('promotional_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'promotional_price',
                'promotional_starts_at',
                'promotional_ends_at',
            ]);
        });
    }
};
