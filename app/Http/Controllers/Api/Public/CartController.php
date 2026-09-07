<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    /**
     * Helper to resolve the cart from session header or auth user.
     */
    private function resolveCart(Request $request): Cart
    {
        $sessionId = $request->header('Cart-Session-Id');
        abort_if(!$sessionId, 400, 'Cart-Session-Id header is required');

        return Cart::firstOrCreate(
            ['session_id' => $sessionId],
            ['user_id' => auth()->id()]
        );
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $cart->load(['items.product', 'items.variant']);

        $totalMinor = 0;
        $items = $cart->items->map(function ($item) use (&$totalMinor) {
            $price = $item->variant ? $item->variant->retail_price_minor : $item->product->retail_price_minor;
            $lineTotal = $price * $item->quantity;
            $totalMinor += $lineTotal;

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'title' => $item->product->title,
                'variant_title' => $item->variant?->title,
                'image_url' => $item->product->image_url,
                'quantity' => $item->quantity,
                'unit_price_minor' => $price,
                'line_total_minor' => $lineTotal,
            ];
        });

        return response()->json([
            'id' => $cart->id,
            'session_id' => $cart->session_id,
            'currency' => $cart->currency,
            'items' => $items,
            'total_minor' => $totalMinor,
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'         => 'nullable|exists:products,id',
            'product_slug'       => 'nullable|string|exists:products,slug',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity'           => 'required|integer|min:1|max:10',
        ]);

        $productId = $validated['product_id'] ?? null;
        if (!$productId && !empty($validated['product_slug'])) {
            $productId = Product::where('slug', $validated['product_slug'])->value('id');
        }

        abort_if(!$productId, 422, 'The product id field is required.');

        $cart = $this->resolveCart($request);

        // Verify product/variant is active
        $product = Product::active()->findOrFail($productId);

        $item = $cart->items()->firstOrNew([
            'product_id'         => $productId,
            'product_variant_id' => $validated['product_variant_id'] ?? null,
        ]);

        $item->quantity = $item->exists ? $item->quantity + $validated['quantity'] : $validated['quantity'];
        $item->save();

        return $this->show($request);
    }

    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:10',
        ]);

        $cart = $this->resolveCart($request);
        $item = $cart->items()->findOrFail($itemId);

        if ($validated['quantity'] === 0) {
            $item->delete();
        } else {
            $item->quantity = $validated['quantity'];
            $item->save();
        }

        return $this->show($request);
    }

    /**
     * Merge a guest cart into the authenticated user's cart.
     */
    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guest_session_id' => 'required|string',
        ]);

        $guestCart = Cart::where('session_id', $validated['guest_session_id'])
            ->whereNull('user_id')
            ->first();

        // If no guest cart, just return the user's cart
        if (!$guestCart) {
            return $this->show($request);
        }

        // Get or create the user's cart (resolved using the current request's session ID and auth)
        $userCart = $this->resolveCart($request);

        // If they are the same cart, nothing to merge
        if ($userCart->id === $guestCart->id) {
            return $this->show($request);
        }

        // Move items from guest cart to user cart
        foreach ($guestCart->items as $guestItem) {
            $userItem = $userCart->items()->firstOrNew([
                'product_id' => $guestItem->product_id,
                'product_variant_id' => $guestItem->product_variant_id,
            ]);

            $userItem->quantity = $userItem->exists
                ? $userItem->quantity + $guestItem->quantity
                : $guestItem->quantity;

            // Cap at 10 items
            if ($userItem->quantity > 10) {
                $userItem->quantity = 10;
            }

            $userItem->save();
        }

        // Delete the guest cart
        $guestCart->delete();

        return $this->show($request);
    }
}
