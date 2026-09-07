<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductSkuService;
use Illuminate\Console\Command;

class RegenerateProductSkus extends Command
{
    protected $signature = 'products:regenerate-skus {--force : Apply the generated codes}';
    protected $description = 'Generate catalog codes from each product collection and title.';

    public function handle(ProductSkuService $skuService): int
    {
        if (! $this->option('force')) {
            $this->warn('Dry run only. Add --force to update product and variant codes.');
            return self::SUCCESS;
        }

        $updated = 0;
        Product::query()->with('collections')->orderBy('id')->each(function (Product $product) use ($skuService, &$updated): void {
            $collection = $product->collections->sortBy('sort_order')->first();
            $sku = $skuService->make($product->title, $collection, $product->id);
            $product->update(['sku' => $sku]);
            $skuService->syncVariantSkus($product->fresh());
            $updated++;
        });

        $this->info("Updated {$updated} product code(s) and their color variants.");
        return self::SUCCESS;
    }
}
