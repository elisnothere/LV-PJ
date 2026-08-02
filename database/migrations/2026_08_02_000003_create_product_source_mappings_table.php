<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_source_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('external_id');
            $table->timestamp('external_updated_at')->nullable();
            $table->string('checksum', 64);
            $table->json('raw_payload');
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index(['product_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_source_mappings');
    }
};
