<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProductFromAutomation implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly array $data) {}

    /**
     * The unique ID of the job based on SKU.
     */
    public function uniqueId(): string
    {
        return $this->data['sku'];
    }

    /**
     * Execute the job.
     */
    public function handle(ProductSyncService $service): void
    {
        $service->sync($this->data);
    }
}
