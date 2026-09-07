<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();
            $table->string('type', 32)->default('shipping'); // shipping | billing
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state', 128)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->char('country_code', 2);
            $table->string('phone', 32)->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'type']);
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
