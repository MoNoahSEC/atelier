<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 64);
            $table->string('event_type', 128);
            $table->string('idempotency_key');
            $table->json('payload_json');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();

            // Prevent duplicate processing from the same source
            $table->unique(['source', 'idempotency_key']);
            $table->index('event_type');
            $table->index('processed_at');
            $table->index(['source', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
