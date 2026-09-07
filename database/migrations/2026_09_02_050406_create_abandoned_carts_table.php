<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // logged-in user
            $table->string('session_id', 100)->nullable()->index();     // guest fallback
            $table->unsignedBigInteger('product_id')->nullable();
            $table->json('cart_data_json')->nullable();                 // snapshot of cart items
            $table->timestamp('abandoned_at')->useCurrent();
            $table->timestamp('followed_up_at')->nullable();
            $table->string('follow_up_note')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
