<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\FulfillmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFulfillmentWebhook implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly array $data,
        public readonly ?string $actorType = null,
        public readonly ?int $actorId = null
    ) {}

    /**
     * The unique ID of the job.
     * Prevents duplicate jobs from being queued for the same idempotency_key.
     */
    public function uniqueId(): string
    {
        return $this->data['idempotency_key'];
    }

    /**
     * Execute the job.
     */
    public function handle(FulfillmentService $service): void
    {
        $service->processFulfillment($this->data, $this->actorType, $this->actorId);
    }
}
