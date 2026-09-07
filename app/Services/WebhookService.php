<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Ingest a webhook event idempotently.
     *
     * Returns [WebhookEvent, bool $isDuplicate]
     *
     * @return array{0: WebhookEvent, 1: bool}
     */
    public function ingest(string $source, string $eventType, string $idempotencyKey, array $payload): array
    {
        // Check for existing event before acquiring a lock
        $existing = WebhookEvent::where('source', $source)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            Log::info('Webhook duplicate rejected', [
                'source'          => $source,
                'event_type'      => $eventType,
                'idempotency_key' => $idempotencyKey,
            ]);
            return [$existing, true];
        }

        // Insert — unique constraint protects against race conditions
        // Use firstOrCreate to handle concurrent inserts safely
        $event = DB::transaction(function () use ($source, $eventType, $idempotencyKey, $payload): WebhookEvent {
            return WebhookEvent::firstOrCreate(
                [
                    'source'          => $source,
                    'idempotency_key' => $idempotencyKey,
                ],
                [
                    'event_type'   => $eventType,
                    'payload_json' => $payload,
                    'attempts'     => 0,
                ]
            );
        });

        $isDuplicate = ! $event->wasRecentlyCreated;

        Log::info('Webhook ingested', [
            'id'              => $event->id,
            'source'          => $source,
            'event_type'      => $eventType,
            'idempotency_key' => $idempotencyKey,
            'is_duplicate'    => $isDuplicate,
        ]);

        return [$event, $isDuplicate];
    }

    /**
     * Mark a webhook event as successfully processed.
     */
    public function markProcessed(WebhookEvent $event): void
    {
        $event->update([
            'processed_at' => now(),
            'failed_at'    => null,
            'attempts'     => $event->attempts + 1,
        ]);
    }

    /**
     * Mark a webhook event as failed.
     */
    public function markFailed(WebhookEvent $event, ?\Throwable $e = null): void
    {
        $event->update([
            'failed_at' => now(),
            'attempts'  => $event->attempts + 1,
        ]);

        Log::error('Webhook processing failed', [
            'id'         => $event->id,
            'source'     => $event->source,
            'event_type' => $event->event_type,
            'attempts'   => $event->attempts,
            'error'      => $e?->getMessage(),
        ]);
    }
}
