<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Session-based Shopping Cart Controller.
 * Cart structure: [ 'productId_variantId' => [ 'product_id', 'variant_id', 'name', 'variant', 'price', 'image', 'qty' ] ]
 */
class CartController extends Controller
{
    // ── GET /cart ─────────────────────────────────────────────────────────────
    public function index(): View
    {
        $cart = session('cart', []);
        $settings = Setting::allAsMap();

        $cartItems = [];
        $subtotal  = 0;

        foreach ($cart as $key => $item) {
            $product = Product::with(['mediaAssets', 'variants'])->find($item['product_id'] ?? 0);
            if (! $product) {
                unset($cart[$key]);
                continue;
            }
            $variant = !empty($item['variant_id']) ? $product->variants->firstWhere('id', $item['variant_id']) : null;
            $cartItems[$key] = $this->makeCartItem($product, $variant, (int) ($item['qty'] ?? 1));
            $subtotal += $cartItems[$key]['price'] * $cartItems[$key]['qty'];
        }
        session(['cart' => $cartItems]);

        return view('cart', compact('cartItems', 'subtotal', 'settings'));
    }

    // ── POST /cart/add ────────────────────────────────────────────────────────
    public function add(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'qty'        => 'nullable|integer|min:1|max:20',
        ]);

        $productId = (int) $request->input('product_id');
        $variantId = $request->input('variant_id') ? (int) $request->input('variant_id') : null;
        $qty       = max(1, (int) $request->input('qty', 1));

        $product = Product::with(['mediaAssets', 'variants'])->findOrFail($productId);

        $variant = $variantId ? $product->variants->firstWhere('id', $variantId) : null;
        abort_if($variantId && ! $variant, 422, 'The selected product option is unavailable.');

        // Cart key = unique combination of product + variant
        $cartKey = $productId . '_' . ($variantId ?? '0');

        $cart = session('cart', []);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['qty'] = min(20, $cart[$cartKey]['qty'] + $qty);
        } else {
            $cart[$cartKey] = $this->makeCartItem($product, $variant, $qty);
        }

        session(['cart' => $cart]);

        return back()->with('success', "«{$product->title}» added to your cart.");
    }

    // ── POST /cart/update ─────────────────────────────────────────────────────
    public function update(Request $request): RedirectResponse
    {
        $key    = $request->input('key');
        $action = $request->input('action'); // 'increase' | 'decrease' | 'set'
        $cart   = session('cart', []);

        if (!isset($cart[$key])) {
            return back();
        }

        if ($action === 'increase') {
            $cart[$key]['qty'] = min(20, ($cart[$key]['qty'] ?? 1) + 1);
        } elseif ($action === 'decrease') {
            $newQty = ($cart[$key]['qty'] ?? 1) - 1;
            if ($newQty <= 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['qty'] = $newQty;
            }
        } elseif ($action === 'set') {
            $qty = max(1, min(20, (int) $request->input('qty', 1)));
            $cart[$key]['qty'] = $qty;
        }

        session(['cart' => $cart]);
        return back();
    }

    // ── POST /cart/remove ─────────────────────────────────────────────────────
    public function remove(Request $request): RedirectResponse
    {
        $key  = $request->input('key');
        $cart = session('cart', []);
        unset($cart[$key]);
        session(['cart' => $cart]);
        return back()->with('success', 'Item removed from your cart.');
    }

    // ── POST /cart/clear ──────────────────────────────────────────────────────
    public function clear(): RedirectResponse
    {
        session()->forget('cart');
        return back()->with('success', 'Your cart has been cleared.');
    }

    // ── Helper: get cart count (for header badge) ─────────────────────────────
    public static function cartCount(): int
    {
        $cart = session('cart', []);
        return array_sum(array_column($cart, 'qty'));
    }

    // ── Helper: get cart subtotal ─────────────────────────────────────────────
    public static function cartSubtotal(): int
    {
        $cart = session('cart', []);
        $total = 0;
        foreach ($cart as $item) {
            $total += ($item['price'] ?? 0) * ($item['qty'] ?? 1);
        }
        return $total;
    }

    /** Build a single reliable display snapshot from the current product option. */
    public function makeCartItem(Product $product, ?ProductVariant $variant, int $qty): array
    {
        $attrs = $variant?->attributes_json ?? [];
        $variantImage = $variant?->image_url ?: ($attrs['image_url'] ?? null);
        $cover = $product->mediaAssets->firstWhere('is_cover', true) ?? $product->mediaAssets->first();
        $image = $this->publicImage($variantImage)
            ?: $this->publicImage($cover?->url)
            ?: $this->publicImage($product->image_url);
        $priceMinor = $variant?->effective_price_minor ?: (int) $product->retail_price_minor;

        return [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'name' => $product->title,
            'variant' => $variant?->title ?: $variant?->attribute_value,
            'color_hex' => $attrs['color_hex'] ?? $attrs['hex'] ?? null,
            'price' => (int) round($priceMinor / 100),
            'image' => $image,
            'qty' => max(1, min(20, $qty)),
        ];
    }

    private function publicImage(?string $path): ?string
    {
        if (! $path) return null;
        return str_starts_with($path, 'http') ? $path : url($path);
    }
}
