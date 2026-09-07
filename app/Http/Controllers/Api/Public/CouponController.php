<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code'           => 'required|string',
            'subtotal_minor' => 'required|integer|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon || !$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired coupon code.',
            ], 422);
        }

        $discount = $coupon->calculateDiscount((int) $request->subtotal_minor);

        if ($discount === 0) {
            return response()->json([
                'success' => false,
                'message' => "Coupon requires a minimum order amount of " . ($coupon->min_order_amount_minor / 100),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'code'           => $coupon->code,
                'discount_minor' => $discount,
                'type'           => $coupon->type,
                'value'          => $coupon->value,
            ],
        ]);
    }
}
