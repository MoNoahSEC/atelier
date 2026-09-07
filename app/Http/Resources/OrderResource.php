<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'order_number'       => $this->order_number,
            'currency'           => $this->currency,
            'subtotal_minor'     => $this->subtotal_minor,
            'shipping_minor'     => $this->shipping_minor,
            'tax_minor'          => $this->tax_minor,
            'discount_minor'     => $this->discount_minor,
            'total_amount_minor' => $this->total_amount_minor,
            'payment_status'     => $this->payment_status,
            'shipping_status'    => $this->shipping_status,
            'fulfillment_status' => $this->fulfillment_status,
            'tracking_number'    => $this->tracking_number,
            'carrier'            => $this->carrier,
            'external_order_id'  => $this->external_order_id,
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
