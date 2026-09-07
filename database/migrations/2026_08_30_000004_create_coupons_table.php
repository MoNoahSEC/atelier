<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type', 20); // percentage, fixed
            $table->unsignedInteger('value'); // percentage (e.g. 10 = 10%) or fixed minor units
            $table->unsignedInteger('min_order_amount_minor')->default(0);
            $table->unsignedInteger('max_discount_minor')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['code', 'is_active']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('coupons');
    }
};
