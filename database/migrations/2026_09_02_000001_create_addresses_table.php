<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('label')->default('Home'); // e.g. Home, Work, Office
            $table->string('full_name');
            $table->string('phone', 32);
            $table->string('street_address');
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('country_code', 2)->default('EG');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
