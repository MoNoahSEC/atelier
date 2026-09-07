<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Atomically adjust product inventory.
     *
     * @param  string $type  Movement type: sale|return|restock|adjustment|reservation|release
     */
    public function adjustProductInventory(
        int $productId,
        int $quantity,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = []
    ): Product {
        return DB::transaction(function () use ($productId, $quantity, $type, $referenceType, $referenceId, $metadata): Product {
            // Lock the product row to prevent concurrent inventory races
            $product = Product::lockForUpdate()->findOrFail($productId);

            $this->assertSufficientStock($product->inventory, $quantity, $type);

            // Atomic increment/decrement
            $product->inventory += $quantity;
            $product->save();

            // Record the movement for audit trail
            InventoryMovement::create([
                'product_id'         => $product->id,
                'product_variant_id' => null,
                'type'               => $type,
                'quantity'           => $quantity,
                'reference_type'     => $referenceType,
                'reference_id'       => $referenceId,
                'metadata_json'      => $metadata ?: null,
            ]);

            Log::info('Inventory adjusted', [
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'type'       => $type,
                'delta'      => $quantity,
                'new_stock'  => $product->inventory,
            ]);

            return $product;
        });
    }

    /**
     * Atomically adjust variant inventory.
     */
    public function adjustVariantInventory(
        int $variantId,
        int $quantity,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = []
    ): ProductVariant {
        return DB::transaction(function () use ($variantId, $quantity, $type, $referenceType, $referenceId, $metadata): ProductVariant {
            $variant = ProductVariant::lockForUpdate()->findOrFail($variantId);

            $this->assertSufficientStock($variant->inventory, $quantity, $type);

            $variant->inventory += $quantity;
            $variant->save();

            InventoryMovement::create([
                'product_id'         => $variant->product_id,
                'product_variant_id' => $variant->id,
                'type'               => $type,
                'quantity'           => $quantity,
                'reference_type'     => $referenceType,
                'reference_id'       => $referenceId,
                'metadata_json'      => $metadata ?: null,
            ]);

            Log::info('Variant inventory adjusted', [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'sku'        => $variant->sku,
                'type'       => $type,
                'delta'      => $quantity,
                'new_stock'  => $variant->inventory,
            ]);

            return $variant;
        });
    }

    /**
     * Ensure a negative quantity (stock deduction) does not exceed current stock.
     */
    private function assertSufficientStock(int $currentStock, int $quantity, string $type): void
    {
        // Only check for outgoing movements
        if ($quantity < 0 && ($currentStock + $quantity) < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock for '{$type}': current={$currentStock}, requested=" . abs($quantity),
            ]);
        }
    }
}
