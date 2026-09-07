<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // new_order, low_stock, payment_failed, new_customer, order_cancelled
            $table->string('title');
            $table->text('message');
            $table->json('data_json')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->index(['is_read', 'created_at']);
            $table->index('type');
        });
    }
    public function down(): void {
        Schema::dropIfExists('admin_notifications');
    }
};
