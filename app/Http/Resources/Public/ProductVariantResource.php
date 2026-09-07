<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'sku'          => $this->sku,
            'price_minor'  => $this->retail_price_minor,
            'retail_price_minor' => $this->retail_price_minor,
            'inventory'    => $this->inventory,
            'is_available' => $this->inventory > 0,
            'image_url'    => $this->attributes_json['image_url'] ?? null,
            'color_hex'    => $this->attributes_json['color_hex'] ?? null,
            'attributes'   => $this->attributes_json,
        ];
    }
}

