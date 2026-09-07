<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->foreignId('product_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->nullOnDelete();
            // type: sale | return | restock | adjustment | reservation | release
            $table->string('type', 32);
            // signed integer: negative = stock out, positive = stock in
            $table->integer('quantity');
            // Polymorphic reference to the source entity (Order, Shipment, etc.)
            $table->string('reference_type', 64)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('product_variant_id');
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
