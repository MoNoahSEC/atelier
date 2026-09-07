<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalRevenue      = Order::where('payment_status', 'paid')->sum('total_amount_minor');
        $ordersToday       = Order::whereDate('created_at', today())->count();
        $pendingFulfillment = Order::where('fulfillment_status', 'unfulfilled')
            ->where('payment_status', 'paid')->count();
        $totalCustomers    = Customer::count();
        $totalProducts     = Product::count();
        $lowStockProducts  = Product::where('inventory', '<=', 5)->where('status', 'active')->count();

        // Revenue last 7 days
        $revenueChart = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(total_amount_minor) as revenue, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Recent orders
        $recentOrders = Order::with('customer')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'id'                 => $o->id,
                'order_number'       => $o->order_number,
                'customer_email'     => $o->customer_email,
                'total_amount_minor' => $o->total_amount_minor,
                'currency'           => $o->currency,
                'payment_status'     => $o->payment_status,
                'fulfillment_status' => $o->fulfillment_status,
                'created_at'         => $o->created_at,
            ]);

        // Low stock products
        $lowStock = Product::where('inventory', '<=', 5)
            ->where('status', 'active')
            ->orderBy('inventory')
            ->limit(10)
            ->get(['id', 'title', 'sku', 'inventory', 'image_url']);

        // Top products
        $topProducts = \Illuminate\Support\Facades\DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->select('products.id', 'products.title', \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // Orders by status
        $ordersByStatus = Order::select('payment_status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status');

        // Recent customers
        $recentCustomers = Customer::orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at']);

        return response()->json([
            'success' => true,
            'data'    => [
                'kpis' => [
                    'total_revenue_minor'  => $totalRevenue,
                    'orders_today'         => $ordersToday,
                    'pending_fulfillment'  => $pendingFulfillment,
                    'total_customers'      => $totalCustomers,
                    'total_products'       => $totalProducts,
                    'low_stock_count'      => $lowStockProducts,
                ],
                'revenue_chart'    => $revenueChart,
                'recent_orders'    => $recentOrders,
                'low_stock'        => $lowStock,
                'top_products'     => $topProducts,
                'orders_by_status' => $ordersByStatus,
                'recent_customers' => $recentCustomers,
            ],
        ]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $orders = Order::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();

        $days = [];
        $totalPaid = 0;
        $totalPending = 0;
        $totalCancelled = 0;
        $totalRevenue = 0;

        foreach ($orders as $order) {
            $date = $order->created_at->format('Y-m-d');
            
            if (!isset($days[$date])) {
                $days[$date] = ['paid' => 0, 'pending' => 0, 'cancelled' => 0, 'total' => 0];
            }
            
            $status = $order->payment_status;
            if (in_array($status, ['paid', 'pending', 'cancelled'])) {
                $days[$date][$status]++;
            } else {
                $days[$date]['pending']++; // fallback for others
            }
            $days[$date]['total']++;

            if ($order->payment_status === 'paid') {
                $totalPaid++;
                $totalRevenue += $order->total_amount_minor;
            } elseif ($order->payment_status === 'cancelled') {
                $totalCancelled++;
            } else {
                $totalPending++;
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'month' => $month,
                'year'  => $year,
                'days'  => $days,
                'summary' => [
                    'total_paid'          => $totalPaid,
                    'total_pending'       => $totalPending,
                    'total_cancelled'     => $totalCancelled,
                    'total_orders'        => $orders->count(),
                    'total_revenue_minor' => $totalRevenue,
                ],
            ],
        ]);
    }
}
