<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_url');
            $table->string('source')->default('url');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('products')
            ->whereNotNull('image_url')
            ->where('image_url', '<>', '')
            ->orderBy('id')
            ->get(['id', 'image_url', 'created_at', 'updated_at'])
            ->each(function ($product) {
                $source = str_starts_with((string) $product->image_url, '/storage/productos/') ? 'upload' : 'url';

                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'image_url' => $product->image_url,
                    'source' => $source,
                    'is_primary' => true,
                    'sort_order' => 1,
                    'created_at' => $product->created_at ?? now(),
                    'updated_at' => $product->updated_at ?? now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
