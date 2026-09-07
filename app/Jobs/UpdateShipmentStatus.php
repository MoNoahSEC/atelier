<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateShipmentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $trackingNumber,
        public readonly string $carrier,
        public readonly string $status
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $shipments = Shipment::where('tracking_number', $this->trackingNumber)
            ->where('carrier', $this->carrier)
            ->get();

        if ($shipments->isEmpty()) {
            return;
        }

        foreach ($shipments as $shipment) {
            if ($shipment->status === $this->status) {
                continue;
            }

            $attrs = ['status' => $this->status];

            if ($this->status === 'shipped' && $shipment->shipped_at === null) {
                $attrs['shipped_at'] = now();
            }

            if ($this->status === 'delivered' && $shipment->delivered_at === null) {
                $attrs['delivered_at'] = now();
            }

            $shipment->update($attrs);
            
            // Sync order shipping status
            $shipment->order->update(['shipping_status' => $this->status]);

            Log::info('Shipment status updated via webhook', [
                'tracking_number' => $this->trackingNumber,
                'status'          => $this->status,
                'order_id'        => $shipment->order_id,
            ]);
        }
    }
}
