<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $globalThreshold = (int) Setting::get('low_stock_threshold', 5);

        $query = Product::with(['variants', 'collections'])->latest();

        if ($request->filled('filter')) {
            if ($request->input('filter') === 'low') {
                $query->where(function ($q) use ($globalThreshold) {
                    $q->where(function ($sub) use ($globalThreshold) {
                        $sub->whereNull('low_stock_threshold')
                            ->where('inventory', '<=', $globalThreshold);
                    })->orWhere(function ($sub) {
                        $sub->whereNotNull('low_stock_threshold')
                            ->whereRaw('inventory <= low_stock_threshold');
                    });
                });
            } elseif ($request->input('filter') === 'out') {
                $query->where('inventory', '<=', 0);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('inventory')->paginate(20)->withQueryString();

        // Calculate 30-day sales velocity and smart reorder suggestions
        $productIds = $products->pluck('id')->toArray();
        $thirtyDaysAgo = now()->subDays(30);

        $salesVelocity = OrderItem::whereIn('product_id', $productIds)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('product_id, SUM(quantity) as units_sold')
            ->groupBy('product_id')
            ->pluck('units_sold', 'product_id')
            ->toArray();

        // Counts
        $outOfStockCount = Product::where('inventory', '<=', 0)->count();
        $lowStockCount = Product::where(function ($q) use ($globalThreshold) {
            $q->where(function ($sub) use ($globalThreshold) {
                $sub->whereNull('low_stock_threshold')
                    ->where('inventory', '<=', $globalThreshold);
            })->orWhere(function ($sub) {
                $sub->whereNotNull('low_stock_threshold')
                    ->whereRaw('inventory <= low_stock_threshold');
            });
        })->count();

        $totalInventoryUnits = Product::sum('inventory');

        return view('admin.inventory.index', compact(
            'products',
            'globalThreshold',
            'outOfStockCount',
            'lowStockCount',
            'totalInventoryUnits',
            'salesVelocity'
        ));
    }

    public function quickUpdateStock(Request $request, int $id)
    {
        $request->validate([
            'inventory' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $product = Product::findOrFail($id);
        $oldStock = $product->inventory;
        $product->inventory = (int) $request->input('inventory');
        if ($request->has('low_stock_threshold')) {
            $product->low_stock_threshold = $request->filled('low_stock_threshold') 
                ? (int) $request->input('low_stock_threshold') 
                : null;
        }
        $product->save();

        AuditLog::log('inventory.update', 'product', $product->id, "Updated stock for {$product->title} from {$oldStock} to {$product->inventory}");

        return back()->with('success', "Stock updated for \"{$product->title}\" ({$product->inventory} units).");
    }

    public function updateGlobalThreshold(Request $request)
    {
        $request->validate([
            'low_stock_threshold' => 'required|integer|min:1',
        ]);

        Setting::set('low_stock_threshold', (string) $request->input('low_stock_threshold'));

        AuditLog::log('settings.inventory', 'setting', 0, "Updated global low stock threshold to {$request->input('low_stock_threshold')}");

        return back()->with('success', "Global low stock threshold updated to {$request->input('low_stock_threshold')} units.");
    }
}
