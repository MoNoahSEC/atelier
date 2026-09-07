<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $product = Product::active()->where('slug', $slug)->firstOrFail();

        $reviews = Review::forProduct($product->id)
            ->approved()
            ->with('customer:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $reviews->items(),
            'meta'    => [
                'total'        => $reviews->total(),
                'per_page'     => $reviews->perPage(),
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $product = Product::active()->where('slug', $slug)->firstOrFail();
        $customer = $request->user()->customer;

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title'  => 'nullable|string|max:255',
            'body'   => 'nullable|string',
        ]);

        $existing = Review::where('product_id', $product->id)
            ->where('customer_id', $customer->id)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product.',
            ], 422);
        }

        // Check if verified purchase
        $hasPurchased = $customer->orders()
            ->where('payment_status', 'paid')
            ->whereHas('items', fn($q) => $q->where('product_id', $product->id))
            ->exists();

        $review = Review::create([
            'product_id'           => $product->id,
            'customer_id'          => $customer->id,
            'rating'               => $request->rating,
            'title'                => $request->title,
            'body'                 => $request->body,
            'is_verified_purchase' => $hasPurchased,
            'is_approved'          => false, // Pending approval
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted and is pending approval.',
            'data'    => $review,
        ], 201);
    }
}
