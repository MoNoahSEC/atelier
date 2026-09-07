<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('cost_price_minor')->default(0);
            $table->unsignedBigInteger('retail_price_minor')->default(0);
            $table->char('currency', 3)->default('USD');
            $table->unsignedInteger('inventory')->default(0);
            $table->json('attributes_json')->nullable();
            $table->string('status', 32)->default('active');
            $table->foreignId('supplier_id')
                  ->nullable()
                  ->constrained('suppliers')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('supplier_id');
            $table->index(['status', 'inventory']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
