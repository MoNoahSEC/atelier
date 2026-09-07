<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'slug'         => $this->slug,
            'title'        => $this->title,
            'description'  => $this->description,
            'image_url'    => $this->image_url,
            'price_minor'  => $this->retail_price_minor,
            'retail_price_minor' => $this->retail_price_minor,
            'currency'     => $this->currency,
            // Only expose a boolean availability, not exact internal counts unless required
            'is_available' => $this->inventory > 0,
            'inventory'    => $this->inventory,
            'attributes'   => $this->attributes_json,
            'attributes_json' => $this->attributes_json,
            'variants'     => ProductVariantResource::collection($this->whenLoaded('variants')),
            'media_assets' => $this->whenLoaded('mediaAssets'),
        ];
    }
}
