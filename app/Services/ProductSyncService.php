<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductSyncService
{
    /**
     * Upsert a product (and optional variants) from automation data.
     * Idempotent: calling with the same SKU updates, not duplicates.
     */
    public function sync(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $supplierId = $this->resolveSupplier($data['supplier_code'] ?? null);

            $productData = [
                'title'              => $data['title'],
                'description'        => $data['description'] ?? null,
                'cost_price_minor'   => $data['cost_price_minor'],
                'retail_price_minor' => $data['retail_price_minor'],
                'currency'           => strtoupper($data['currency']),
                'attributes_json'    => isset($data['attributes']) ? $data['attributes'] : null,
                'status'             => $data['status'] ?? 'active',
                'supplier_id'        => $supplierId,
            ];

            // Auto-generate slug from title if not provided
            if (! empty($data['slug'])) {
                $productData['slug'] = $data['slug'];
            }

            $product = Product::where('sku', $data['sku'])->lockForUpdate()->first();

            if ($product === null) {
                $productData['sku']  = $data['sku'];
                $productData['slug'] = $productData['slug'] ?? $this->generateUniqueSlug($data['title'], $data['sku']);
                // Default inventory on first creation only
                $productData['inventory'] = $data['inventory'] ?? 0;

                $product = Product::create($productData);

                Log::info('Product created via sync', ['sku' => $product->sku, 'id' => $product->id]);
            } else {
                // Do not overwrite inventory from sync unless explicitly provided
                if (isset($data['inventory'])) {
                    $productData['inventory'] = $data['inventory'];
                }

                $product->update($productData);

                Log::info('Product updated via sync', ['sku' => $product->sku, 'id' => $product->id]);
            }

            // Sync variants if provided
            if (! empty($data['variants'])) {
                $this->syncVariants($product, $data['variants']);
            }

            return $product->fresh();
        });
    }

    /**
     * Resolve or create a supplier by code.
     */
    private function resolveSupplier(?string $supplierCode): ?int
    {
        if ($supplierCode === null) {
            return null;
        }

        $supplier = Supplier::where('code', $supplierCode)->first();

        return $supplier?->id;
    }

    /**
     * Upsert variants for a product.
     */
    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variantData) {
            $attrs = [
                'title'              => $variantData['title'],
                'cost_price_minor'   => $variantData['cost_price_minor'],
                'retail_price_minor' => $variantData['retail_price_minor'],
                'attributes_json'    => $variantData['attributes'] ?? null,
                'status'             => $variantData['status'] ?? 'active',
            ];

            $existing = ProductVariant::where('product_id', $product->id)
                ->where('sku', $variantData['sku'])
                ->lockForUpdate()
                ->first();

            if ($existing === null) {
                $attrs['inventory'] = $variantData['inventory'] ?? 0;
                ProductVariant::create(array_merge([
                    'product_id' => $product->id,
                    'sku'        => $variantData['sku'],
                ], $attrs));
            } else {
                if (isset($variantData['inventory'])) {
                    $attrs['inventory'] = $variantData['inventory'];
                }
                $existing->update($attrs);
            }
        }
    }

    /**
     * Generate a URL-safe slug that is unique in the products table.
     */
    private function generateUniqueSlug(string $title, string $sku): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . Str::slug($sku) . ($i > 1 ? "-{$i}" : '');
            $i++;
        }

        return $slug;
    }
}
