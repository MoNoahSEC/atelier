<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->string('sku');
            $table->string('title');
            $table->json('attributes_json')->nullable();
            $table->unsignedBigInteger('cost_price_minor')->default(0);
            $table->unsignedBigInteger('retail_price_minor')->default(0);
            $table->unsignedInteger('inventory')->default(0);
            $table->string('status', 32)->default('active');
            $table->timestamps();

            // SKU unique per product
            $table->unique(['product_id', 'sku']);
            $table->index(['product_id', 'status']);
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
