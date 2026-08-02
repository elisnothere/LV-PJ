<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('external_url')->nullable()->after('image_url');
            $table->string('checksum', 64)->nullable()->after('external_url');

            $table->unique(['product_id', 'external_url']);
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'external_url']);
            $table->dropColumn(['external_url', 'checksum']);
        });
    }
};
