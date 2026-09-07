<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchPendingOrder implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $orderId) {}

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::with('items', 'customer')->find($this->orderId);

        if (! $order || ! $order->canBeFulfilled()) {
            return;
        }

        // Example integration: push order to external system
        $endpoint = config('automation.outbound.fulfillment_endpoint');
        
        if (! $endpoint) {
            Log::warning('DispatchPendingOrder skipped: no outbound endpoint configured', ['order_id' => $this->orderId]);
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('automation.outbound.token'),
        ])->post($endpoint, [
            'order_number' => $order->order_number,
            'items'        => $order->items->map(fn($item) => [
                'sku'      => $item->sku,
                'quantity' => $item->quantity,
            ])->toArray(),
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        Log::info('Order dispatched to external system', ['order_id' => $this->orderId]);
    }
}
