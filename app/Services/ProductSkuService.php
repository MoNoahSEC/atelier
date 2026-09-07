<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

/** Generates readable, collision-free catalog codes without asking the admin to type one. */
class ProductSkuService
{
    public function make(string $title, ?Collection $collection = null, ?int $excludingProductId = null): string
    {
        $collectionPart = $this->codePart($collection?->title ?? $collection?->slug ?? 'catalog', 4, 'CAT');
        $productPart = $this->codePart($title, 6, 'ITEM');
        $base = "{$collectionPart}-{$productPart}";

        $number = 1;
        do {
            $sku = $base . '-' . str_pad((string) $number++, 3, '0', STR_PAD_LEFT);
            $exists = Product::query()->where('sku', $sku)
                ->when($excludingProductId, fn ($query) => $query->whereKeyNot($excludingProductId))
                ->exists();
        } while ($exists);

        return $sku;
    }

    public function syncVariantSkus(Product $product): void
    {
        $used = [];
        ProductVariant::query()->where('product_id', $product->id)->orderBy('id')->get()
            ->each(function (ProductVariant $variant) use ($product, &$used): void {
                $suffix = $this->codePart($variant->title ?: $variant->attribute_value ?: 'variant', 10, 'VAR');
                $used[$suffix] = ($used[$suffix] ?? 0) + 1;
                if ($used[$suffix] > 1) {
                    $suffix .= '-' . $used[$suffix];
                }
                $variant->update(['sku' => "{$product->sku}-{$suffix}"]);
            });
    }

    private function codePart(string $value, int $length, string $fallback): string
    {
        $ascii = Str::ascii($value);
        $compact = preg_replace('/[^A-Za-z0-9]+/', '', $ascii) ?: '';

        return strtoupper(Str::substr($compact, 0, $length) ?: $fallback);
    }
}
