<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\PageView;
use App\Models\UserPresence;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // ─── Aggregate stats (safe to cache for 5 minutes) ───────────────────────
        $stats = Cache::remember('admin.dashboard.stats', 300, function () {
            $today     = now()->startOfDay();
            $thisWeek  = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();

            return [
                'revenueToday'     => Order::where('created_at', '>=', $today)->where('shipping_status', '!=', 'cancelled')->sum('total_amount_minor'),
                'revenueWeek'      => Order::where('created_at', '>=', $thisWeek)->where('shipping_status', '!=', 'cancelled')->sum('total_amount_minor'),
                'revenueMonth'     => Order::where('created_at', '>=', $thisMonth)->where('shipping_status', '!=', 'cancelled')->sum('total_amount_minor'),
                'totalRevenue'     => Order::where('shipping_status', '!=', 'cancelled')->sum('total_amount_minor'),
                'totalOrders'      => Order::count(),
                'pendingOrders'    => Order::where('shipping_status', 'pending')->count(),
                'processingOrders' => Order::where('shipping_status', 'processing')->count(),
                'totalProducts'    => Product::count(),
                'totalCustomers'   => User::where('is_admin', false)->count(),
                'viewsToday'       => PageView::where('created_at', '>=', now()->startOfDay())->count(),
                'visitorsToday'    => PageView::where('created_at', '>=', now()->startOfDay())->distinct('visitor_id')->count('visitor_id'),
                // Chart data
                'chartData'        => self::buildChartData(),
            ];
        });

        // ─── Real-time data (never cached) ───────────────────────────────────────
        $lowStockProducts   = Product::where('inventory', '<=', 5)->orderBy('inventory')->get();
        $lowStockCount      = $lowStockProducts->count();
        $recentOrders       = Order::with(['items', 'customer'])->latest()->take(10)->get();
        $unreadNotifications = AdminNotification::where('is_read', false)->count();
        // Presence is intentionally a tiny 90-second window, not a long-lived tracking profile.
        if (Schema::hasTable('user_presences')) {
            $onlineNow = UserPresence::where('last_seen_at', '>=', now()->subSeconds(90));
            $onlineVisitors = (clone $onlineNow)->where('area', 'storefront')->count();
            $onlineAdmins = (clone $onlineNow)->where('area', 'admin')->with('user:id,name')->get();
            $onlineCustomers = (clone $onlineNow)->where('area', 'storefront')->whereNotNull('user_id')->with('user:id,name')->get();
        } else {
            // Safe during zero-downtime rollout or an isolated test database.
            $onlineVisitors = 0;
            $onlineAdmins = collect();
            $onlineCustomers = collect();
        }

        // ─── Chart data from cached stats ────────────────────────────────────────
        $chartLabels      = $stats['chartData']['labels'];
        $chartRevenue     = $stats['chartData']['revenue'];
        $bestsellerLabels = $stats['chartData']['bestsellerLabels'];
        $bestsellerUnits  = $stats['chartData']['bestsellerUnits'];
        $categoryLabels   = $stats['chartData']['categoryLabels'];
        $categoryCounts   = $stats['chartData']['categoryCounts'];

        return view('admin.dashboard', [
            'revenueToday'      => $stats['revenueToday'],
            'revenueWeek'       => $stats['revenueWeek'],
            'revenueMonth'      => $stats['revenueMonth'],
            'totalRevenue'      => $stats['totalRevenue'],
            'totalOrders'       => $stats['totalOrders'],
            'pendingOrders'     => $stats['pendingOrders'],
            'processingOrders'  => $stats['processingOrders'],
            'totalProducts'     => $stats['totalProducts'],
            'totalCustomers'    => $stats['totalCustomers'],
            'viewsToday'        => $stats['viewsToday'],
            'visitorsToday'     => $stats['visitorsToday'],
            'lowStockProducts'  => $lowStockProducts,
            'lowStockCount'     => $lowStockCount,
            'recentOrders'      => $recentOrders,
            'unreadNotifications' => $unreadNotifications,
            'onlineVisitors'      => $onlineVisitors,
            'onlineAdmins'        => $onlineAdmins,
            'onlineCustomers'     => $onlineCustomers,
            'chartLabels'       => $chartLabels,
            'chartRevenue'      => $chartRevenue,
            'bestsellerLabels'  => $bestsellerLabels,
            'bestsellerUnits'   => $bestsellerUnits,
            'categoryLabels'    => $categoryLabels,
            'categoryCounts'    => $categoryCounts,
        ]);
    }

    private static function buildChartData(): array
    {
        $days      = 30;
        $startDate = now()->subDays($days - 1)->startOfDay();

        $dailyOrders = Order::where('created_at', '>=', $startDate)
            ->where('shipping_status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount_minor) as total_minor')
            ->groupBy('date')
            ->pluck('total_minor', 'date')
            ->toArray();

        $labels  = [];
        $revenue = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dateKey  = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d M');
            $revenue[] = round(($dailyOrders[$dateKey] ?? 0) / 100, 2);
        }

        $bestSellersQuery = OrderItem::selectRaw('product_title, SUM(quantity) as total_qty')
            ->groupBy('product_title')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $bestsellerLabels = $bestSellersQuery->pluck('product_title')->toArray();
        $bestsellerUnits  = $bestSellersQuery->pluck('total_qty')->map(fn($v) => (int)$v)->toArray();

        if (empty($bestsellerLabels)) {
            $sampleProducts   = Product::take(5)->get();
            $bestsellerLabels = $sampleProducts->pluck('title')->toArray();
            $bestsellerUnits  = [12, 9, 7, 5, 3];
        }

        $collections    = Collection::withCount('products')->get();
        $categoryLabels = $collections->pluck('title')->toArray();
        $categoryCounts = $collections->pluck('products_count')->toArray();

        return compact('labels', 'revenue', 'bestsellerLabels', 'bestsellerUnits', 'categoryLabels', 'categoryCounts');
    }
}
