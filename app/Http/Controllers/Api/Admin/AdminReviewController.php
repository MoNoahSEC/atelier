<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['product:id,title,slug', 'customer:id,first_name,last_name,email'])
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->has('is_approved'), fn($q) => $q->where('is_approved', filter_var($request->is_approved, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->rating, fn($q) => $q->where('rating', $request->rating))
            ->orderByDesc('created_at');

        $reviews = $query->paginate($request->integer('per_page', 20));

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

    public function approve(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => true]);

        return response()->json([
            'success' => true,
            'data'    => $review,
        ]);
    }

    public function reject(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => false]);

        return response()->json([
            'success' => true,
            'data'    => $review,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }
}
