<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * GET /admin/search?q=term
     * Returns grouped JSON results for the admin global search bar.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['products' => [], 'orders' => [], 'customers' => []]);
        }

        $like = '%' . $q . '%';

        // Products — top 5
        $products = Product::where(function ($query) use ($like) {
            $query->where('title', 'like', $like)
                  ->orWhere('sku', 'like', $like);
        })
        ->select('id', 'title', 'sku', 'retail_price_minor', 'status', 'image_url', 'slug')
        ->latest()
        ->limit(5)
        ->get()
        ->map(fn($p) => [
            'id'     => $p->id,
            'label'  => $p->title,
            'sub'    => $p->sku . ' · ' . number_format($p->retail_price_minor / 100, 0) . ' EGP',
            'badge'  => $p->status,
            'url'    => route('admin.products.edit', $p->id),
            'img'    => $p->image_url ? (str_starts_with($p->image_url, 'http') ? $p->image_url : url($p->image_url)) : null,
        ]);

        // Orders — top 5
        $orders = Order::where(function ($query) use ($like, $q) {
            $query->where('order_number', 'like', $like)
                  ->orWhere('customer_email', 'like', $like)
                  ->orWhere('notes', 'like', '%' . $q . '%');
        })
        ->select('id', 'order_number', 'customer_email', 'total_amount_minor', 'shipping_status', 'created_at', 'metadata_json')
        ->latest()
        ->limit(5)
        ->get()
        ->map(fn($o) => [
            'id'    => $o->id,
            'label' => '#' . $o->order_number,
            'sub'   => ($o->customer_name ?? $o->customer_email) . ' · ' . number_format($o->total_amount_minor / 100, 0) . ' EGP',
            'badge' => $o->shipping_status,
            'url'   => route('admin.orders.show', $o->id),
        ]);

        // Customers — top 5
        $customers = User::where('is_admin', false)
        ->where(function ($query) use ($like) {
            $query->where('name', 'like', $like)
                  ->orWhere('email', 'like', $like);
        })
        ->select('id', 'name', 'email', 'created_at')
        ->latest()
        ->limit(5)
        ->get()
        ->map(fn($c) => [
            'id'    => $c->id,
            'label' => $c->name,
            'sub'   => $c->email,
            'badge' => 'customer',
            'url'   => route('admin.customers.show', $c->id),
        ]);

        return response()->json([
            'products'  => $products,
            'orders'    => $orders,
            'customers' => $customers,
        ]);
    }
}
