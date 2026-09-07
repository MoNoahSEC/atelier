<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        
        $wishlist = $customer->wishlists()->with('product')->get();

        return response()->json([
            'success' => true,
            'data'    => $wishlist,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $product = Product::active()->findOrFail($request->product_id);
        $customer = $request->user()->customer;

        $wishlist = $customer->wishlists()->firstOrCreate([
            'product_id' => $product->id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $wishlist,
        ], 201);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $customer = $request->user()->customer;

        $customer->wishlists()->where('product_id', $productId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from wishlist.',
        ]);
    }
}
