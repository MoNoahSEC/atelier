<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $range = $request->input('range', '30'); // 7, 30, all
        $startDate = match ($range) {
            '7'  => now()->subDays(7)->startOfDay(),
            '30' => now()->subDays(30)->startOfDay(),
            default => now()->subYear()->startOfDay(),
        };

        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        // High level overview
        $viewsToday = PageView::where('created_at', '>=', $today)->count();
        $visitorsToday = PageView::where('created_at', '>=', $today)->distinct('visitor_id')->count('visitor_id');

        $viewsWeek = PageView::where('created_at', '>=', $thisWeek)->count();
        $visitorsWeek = PageView::where('created_at', '>=', $thisWeek)->distinct('visitor_id')->count('visitor_id');

        $viewsMonth = PageView::where('created_at', '>=', $thisMonth)->count();
        $visitorsMonth = PageView::where('created_at', '>=', $thisMonth)->distinct('visitor_id')->count('visitor_id');

        $viewsTotal = PageView::count();
        $visitorsTotal = PageView::distinct('visitor_id')->count('visitor_id');

        // Most viewed products
        $topProductsQuery = PageView::where('page_type', 'product')
            ->whereNotNull('page_id')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('page_id, COUNT(*) as views_count, COUNT(DISTINCT visitor_id) as unique_visitors')
            ->groupBy('page_id')
            ->orderByDesc('views_count')
            ->take(10)
            ->get();

        $productIds = $topProductsQuery->pluck('page_id')->toArray();
        $productsMap = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $topProducts = $topProductsQuery->map(function ($item) use ($productsMap) {
            $product = $productsMap->get($item->page_id);
            return (object) [
                'product_id'      => $item->page_id,
                'title'           => $product ? $product->title : "Product #{$item->page_id}",
                'sku'             => $product ? $product->sku : 'N/A',
                'image_url'       => $product ? $product->image_url : null,
                'retail_price'    => $product ? ($product->retail_price_minor / 100) : 0,
                'inventory'       => $product ? $product->inventory : 0,
                'views_count'     => $item->views_count,
                'unique_visitors' => $item->unique_visitors,
            ];
        });

        // Traffic breakdown by page type
        $pageTypeBreakdown = PageView::where('created_at', '>=', $startDate)
            ->selectRaw('page_type, COUNT(*) as count')
            ->groupBy('page_type')
            ->get()
            ->keyBy('page_type');

        // New Signups trend (last 14 days)
        $signupsTrend = User::where('is_admin', false)
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.analytics.index', compact(
            'range',
            'viewsToday',
            'visitorsToday',
            'viewsWeek',
            'visitorsWeek',
            'viewsMonth',
            'visitorsMonth',
            'viewsTotal',
            'visitorsTotal',
            'topProducts',
            'pageTypeBreakdown',
            'signupsTrend'
        ));
    }
}
