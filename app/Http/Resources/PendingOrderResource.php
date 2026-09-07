<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal representation of a pending order for automation fulfillment.
 * Only exposes fields required by the automation client; avoids over-fetching.
 */
class PendingOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'order_number'       => $this->order_number,
            'external_order_id'  => $this->external_order_id,
            'customer_email'     => $this->customer_email,
            'currency'           => $this->currency,
            'total_amount_minor' => $this->total_amount_minor,
            'fulfillment_status' => $this->fulfillment_status,
            'payment_status'     => $this->payment_status,
            'created_at'         => $this->created_at?->toISOString(),
            'items'              => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'sku'               => $item->sku,
                'product_title'     => $item->product_title,
                'quantity'          => $item->quantity,
                'unit_price_minor'  => $item->unit_price_minor,
                'total_price_minor' => $item->total_price_minor,
            ])),
        ];
    }
}
