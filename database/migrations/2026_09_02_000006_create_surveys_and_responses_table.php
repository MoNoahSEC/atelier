<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('question');
            $table->enum('type', ['rating', 'single_choice', 'text'])->default('rating');
            $table->json('options_json')->nullable(); // For single_choice e.g. ["High Quality", "Fast Delivery", "Packaging"]
            $table->boolean('is_active')->default(true);
            $table->string('target_page')->default('all'); // all, checkout, order_confirmation, home
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->integer('rating')->nullable(); // 1 to 5
            $table->string('selected_option')->nullable();
            $table->text('response_text')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['survey_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('surveys');
    }
};
