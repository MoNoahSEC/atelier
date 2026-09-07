<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->nullOnDelete();
            $table->string('customer_email');
            $table->char('currency', 3)->default('USD');
            $table->unsignedBigInteger('subtotal_minor')->default(0);
            $table->unsignedBigInteger('shipping_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('total_amount_minor')->default(0);
            // payment_status: pending | paid | partially_paid | refunded | failed | cancelled
            $table->string('payment_status', 32)->default('pending');
            // shipping_status: pending | processing | shipped | delivered | returned | cancelled
            $table->string('shipping_status', 32)->default('pending');
            // fulfillment_status: unfulfilled | partially_fulfilled | fulfilled | cancelled
            $table->string('fulfillment_status', 32)->default('unfulfilled');
            $table->string('tracking_number')->nullable();
            $table->string('carrier', 64)->nullable();
            $table->string('external_order_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('customer_email');
            $table->index('payment_status');
            $table->index('shipping_status');
            $table->index('fulfillment_status');
            $table->index('external_order_id');
            $table->index('created_at');
            $table->index(['fulfillment_status', 'payment_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
