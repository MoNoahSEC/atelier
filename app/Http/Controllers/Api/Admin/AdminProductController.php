<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('supplier')
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');

        $products = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $products->items(),
            'meta'    => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['supplier', 'variants', 'collections', 'mediaAssets'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku'                => 'required|string|max:100|unique:products,sku',
            'title'              => 'required|string|max:255',
            'slug'               => 'nullable|string|max:255|unique:products,slug',
            'description'        => 'nullable|string',
            'image_url'          => 'nullable|string',
            'cost_price_minor'   => 'required|integer|min:0',
            'retail_price_minor' => 'required|integer|min:0',
            'currency'           => 'required|string|size:3',
            'inventory'          => 'required|integer|min:0',
            'status'             => 'required|in:active,inactive,archived',
            'supplier_id'        => 'nullable|exists:suppliers,id',
            'attributes_json'    => 'nullable|array',
            'collections'        => 'nullable|array',
            'collections.*'      => 'exists:collections,id',
            'variants'           => 'nullable|array',
            'variants.*.sku'     => 'required|string|max:100',
            'variants.*.title'   => 'required|string|max:255',
            'variants.*.cost_price_minor' => 'nullable|integer|min:0',
            'variants.*.retail_price_minor' => 'required|integer|min:0',
            'variants.*.inventory'=> 'required|integer|min:0',
            'variants.*.attributes_json' => 'nullable|array',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $collections = $validated['collections'] ?? null;
        unset($validated['collections']);
        
        $variants = $validated['variants'] ?? [];
        unset($validated['variants']);

        $product = DB::transaction(function () use ($validated, $collections, $variants) {
            $p = Product::create($validated);
            if ($collections !== null) {
                $p->collections()->sync($collections);
            }
            foreach ($variants as $v) {
                $p->variants()->create($v);
            }
            return $p;
        });

        return response()->json(['success' => true, 'data' => $product->load('collections', 'variants')], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'sku'                => "sometimes|string|max:100|unique:products,sku,{$id}",
            'title'              => 'sometimes|string|max:255',
            'slug'               => "sometimes|string|max:255|unique:products,slug,{$id}",
            'description'        => 'nullable|string',
            'image_url'          => 'nullable|string',
            'cost_price_minor'   => 'sometimes|integer|min:0',
            'retail_price_minor' => 'sometimes|integer|min:0',
            'currency'           => 'sometimes|string|size:3',
            'inventory'          => 'sometimes|integer|min:0',
            'status'             => 'sometimes|in:active,inactive,archived',
            'supplier_id'        => 'nullable|exists:suppliers,id',
            'attributes_json'    => 'nullable|array',
            'collections'        => 'nullable|array',
            'collections.*'      => 'exists:collections,id',
            'variants'           => 'nullable|array',
            'variants.*.id'      => 'nullable|integer|exists:product_variants,id',
            'variants.*.sku'     => 'required|string|max:100',
            'variants.*.title'   => 'required|string|max:255',
            'variants.*.cost_price_minor' => 'nullable|integer|min:0',
            'variants.*.retail_price_minor' => 'required|integer|min:0',
            'variants.*.inventory'=> 'required|integer|min:0',
            'variants.*.attributes_json' => 'nullable|array',
        ]);

        $collections = $validated['collections'] ?? null;
        unset($validated['collections']);
        
        $variants = $validated['variants'] ?? null;
        unset($validated['variants']);

        DB::transaction(function () use ($product, $validated, $collections, $variants) {
            $product->update($validated);

            if ($collections !== null) {
                $product->collections()->sync($collections);
            }

            if ($variants !== null) {
                $existingVariantIds = $product->variants()->pluck('id')->toArray();
                $newVariantIds = [];

                foreach ($variants as $v) {
                    if (isset($v['id']) && in_array($v['id'], $existingVariantIds)) {
                        $product->variants()->where('id', $v['id'])->update($v);
                        $newVariantIds[] = $v['id'];
                    } else {
                        $newVariant = $product->variants()->create($v);
                        $newVariantIds[] = $newVariant->id;
                    }
                }

                $product->variants()->whereNotIn('id', $newVariantIds)->delete();
            }
        });

        return response()->json(['success' => true, 'data' => $product->fresh(['collections', 'mediaAssets', 'variants'])]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['success' => true, 'message' => 'Product deleted.']);
    }

    public function adjustInventory(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|integer',
            'reason'   => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($product, $validated) {
            $lockedProduct = \App\Models\Product::where('id', $product->id)->lockForUpdate()->first();
            $lockedProduct->increment('inventory', $validated['quantity']);

            $lockedProduct->inventoryMovements()->create([
                'type'          => $validated['quantity'] > 0 ? 'restock' : 'adjustment',
                'quantity'      => $validated['quantity'],
                'reference_type' => 'admin',
                'metadata_json'  => ['reason' => $validated['reason'] ?? 'Manual admin adjustment'],
            ]);
        });

        return response()->json(['success' => true, 'data' => ['inventory' => $product->fresh()->inventory]]);
    }
    
    public function attachMedia(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'media_asset_id' => 'required|exists:media_assets,id',
            'group'          => 'nullable|string|max:50',
        ]);

        // Check if already attached
        if (!$product->mediaAssets()->where('media_asset_id', $validated['media_asset_id'])->exists()) {
            $product->mediaAssets()->attach($validated['media_asset_id'], [
                'group'      => $validated['group'] ?? 'gallery',
                'sort_order' => (int) $product->mediaAssets()->withPivot('sort_order')->max('mediables.sort_order') + 1,
            ]);
        }

        return response()->json(['success' => true, 'data' => $product->fresh('mediaAssets')]);
    }

    public function detachMedia(int $id, int $assetId): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->mediaAssets()->detach($assetId);

        return response()->json(['success' => true, 'data' => $product->fresh('mediaAssets')]);
    }

    public function reorderMedia(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'integer|exists:media_assets,id',
        ]);

        foreach ($validated['ordered_ids'] as $index => $assetId) {
            $product->mediaAssets()->updateExistingPivot($assetId, ['sort_order' => $index]);
        }

        return response()->json(['success' => true, 'data' => $product->fresh('mediaAssets')]);
    }
}
