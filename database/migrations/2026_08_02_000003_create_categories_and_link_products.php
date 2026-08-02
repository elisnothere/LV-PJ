<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('canonical_key')->constrained()->restrictOnDelete();
        });

        $timestamps = [
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $categoryIdsBySlug = [];

        DB::table('products')
            ->select(['category'])
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->get()
            ->each(function ($productCategory) use (&$categoryIdsBySlug, $timestamps) {
                $name = trim((string) $productCategory->category);
                $slug = \App\Models\Category::makeSlug($name);

                if (! array_key_exists($slug, $categoryIdsBySlug)) {
                    $existingId = DB::table('categories')->where('slug', $slug)->value('id');

                    if (! $existingId) {
                        $existingId = DB::table('categories')->insertGetId([
                            'name' => $name !== '' ? $name : 'Sin categoria',
                            'slug' => $slug,
                            ...$timestamps,
                        ]);
                    }

                    $categoryIdsBySlug[$slug] = $existingId;
                }
            });

        DB::table('products')
            ->select(['id', 'category'])
            ->orderBy('id')
            ->get()
            ->each(function ($product) use (&$categoryIdsBySlug, $timestamps) {
                $name = trim((string) $product->category);
                $slug = \App\Models\Category::makeSlug($name);
                $categoryId = $categoryIdsBySlug[$slug] ?? null;

                if (! $categoryId) {
                    $categoryId = DB::table('categories')->insertGetId([
                        'name' => $name !== '' ? $name : 'Sin categoria',
                        'slug' => $slug,
                        ...$timestamps,
                    ]);

                    $categoryIdsBySlug[$slug] = $categoryId;
                }

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['category_id' => $categoryId]);
            });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
        });

        $categoryNames = DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select(['products.id', 'categories.name as category_name'])
            ->orderBy('products.id')
            ->get();

        foreach ($categoryNames as $product) {
            DB::table('products')
                ->where('id', $product->id)
                ->update(['category' => $product->category_name ?? 'Sin categoria']);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
