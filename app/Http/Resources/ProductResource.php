<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'sku'                => $this->sku,
            'slug'               => $this->slug,
            'title'              => $this->title,
            'description'        => $this->description,
            'cost_price_minor'   => $this->cost_price_minor,
            'retail_price_minor' => $this->retail_price_minor,
            'currency'           => $this->currency,
            'inventory'          => $this->inventory,
            'attributes'         => $this->attributes_json,
            'status'             => $this->status,
            'supplier_id'        => $this->supplier_id,
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
