<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete();
            $table->string('provider', 64);
            $table->string('provider_transaction_id')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('USD');
            // status: pending | completed | failed | refunded | cancelled
            $table->string('status', 32)->default('pending');
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('provider');
            $table->index('provider_transaction_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
