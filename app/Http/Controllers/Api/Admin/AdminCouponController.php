<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::query()
            ->when($request->search, fn($q) => $q->where('code', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at');

        $coupons = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $coupons->items(),
            'meta'    => [
                'total'        => $coupons->total(),
                'per_page'     => $coupons->perPage(),
                'current_page' => $coupons->currentPage(),
                'last_page'    => $coupons->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'                   => 'required|string|max:50|unique:coupons,code',
            'type'                   => 'required|in:percentage,fixed',
            'value'                  => 'required|integer|min:1',
            'min_order_amount_minor' => 'nullable|integer|min:0',
            'max_discount_minor'     => 'nullable|integer|min:1',
            'max_uses'               => 'nullable|integer|min:1',
            'starts_at'              => 'nullable|date',
            'expires_at'             => 'nullable|date|after_or_equal:starts_at',
            'is_active'              => 'nullable|boolean',
        ]);

        $coupon = Coupon::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $coupon,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $coupon,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        $validated = $request->validate([
            'code'                   => "required|string|max:50|unique:coupons,code,{$id}",
            'type'                   => 'required|in:percentage,fixed',
            'value'                  => 'required|integer|min:1',
            'min_order_amount_minor' => 'nullable|integer|min:0',
            'max_discount_minor'     => 'nullable|integer|min:1',
            'max_uses'               => 'nullable|integer|min:1',
            'starts_at'              => 'nullable|date',
            'expires_at'             => 'nullable|date|after_or_equal:starts_at',
            'is_active'              => 'nullable|boolean',
        ]);

        $coupon->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $coupon,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully.',
        ]);
    }
}
