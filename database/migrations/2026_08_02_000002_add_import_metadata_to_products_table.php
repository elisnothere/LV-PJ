<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('canonical_key')->nullable()->after('name');
            $table->string('vendor')->nullable()->after('category');
            $table->string('primary_source')->nullable()->after('image_url');

            $table->index('canonical_key');
            $table->index('primary_source');
        });

        DB::table('products')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function ($product) {
                $canonical = Str::of((string) $product->name)
                    ->lower()
                    ->ascii()
                    ->replaceMatches('/[^a-z0-9]+/', '-')
                    ->trim('-')
                    ->value();

                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'canonical_key' => $canonical !== '' ? $canonical : 'producto-sin-nombre',
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['canonical_key']);
            $table->dropIndex(['primary_source']);
            $table->dropColumn(['canonical_key', 'vendor', 'primary_source']);
        });
    }
};
