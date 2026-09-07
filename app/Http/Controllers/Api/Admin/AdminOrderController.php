<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer'])
            ->when($request->search, fn($q) => $q->where('order_number', 'like', "%{$request->search}%")
                ->orWhere('customer_email', 'like', "%{$request->search}%"))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->fulfillment_status, fn($q) => $q->where('fulfillment_status', $request->fulfillment_status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at');

        $orders = $query->paginate($request->integer('per_page', 20));

        $items = array_map(fn($o) => [
            'id'                 => $o->id,
            'order_number'       => $o->order_number,
            'customer_email'     => $o->customer_email,
            'currency'           => $o->currency,
            'total_amount_minor' => $o->total_amount_minor,
            'payment_method'     => $o->payment_method,
            'payment_status'     => $o->payment_status,
            'fulfillment_status' => $o->fulfillment_status,
            'shipping_status'    => $o->shipping_status,
            'tracking_number'    => $o->tracking_number,
            'created_at'         => $o->created_at,
        ], $orders->items());

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'total'        => $orders->total(),
                'per_page'     => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with(['items', 'payments', 'shipments', 'customer'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $order]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'payment_status'     => 'sometimes|in:pending,paid,failed,refunded',
            'fulfillment_status' => 'sometimes|in:unfulfilled,fulfilling,fulfilled,cancelled',
            'shipping_status'    => 'sometimes|in:pending,shipped,delivered,returned',
            'tracking_number'    => 'sometimes|nullable|string|max:255',
            'carrier'            => 'sometimes|nullable|string|max:100',
            'notes'              => 'sometimes|nullable|string',
        ]);

        $order->update($validated);

        return response()->json(['success' => true, 'data' => $order->fresh()]);
    }

    public function stats(): JsonResponse
    {
        $today = now()->toDateString();

        $totalRevenue      = Order::where('payment_status', 'paid')->sum('total_amount_minor');
        $ordersToday       = Order::whereDate('created_at', $today)->count();
        $pendingFulfillment = Order::where('fulfillment_status', 'unfulfilled')
            ->where('payment_status', 'paid')->count();
        $totalOrders       = Order::count();

        $revenueByDay = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(total_amount_minor) as revenue, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_revenue_minor'  => $totalRevenue,
                'orders_today'         => $ordersToday,
                'pending_fulfillment'  => $pendingFulfillment,
                'total_orders'         => $totalOrders,
                'revenue_by_day'       => $revenueByDay,
            ],
        ]);
    }
}
