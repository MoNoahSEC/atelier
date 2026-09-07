<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with(['items', 'customer'])->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('shipping_status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $orders = $query->paginate(15)->withQueryString();

        // Status counts for quick filter pills
        $counts = [
            'all'        => Order::count(),
            'pending'    => Order::where('shipping_status', 'pending')->count(),
            'processing' => Order::where('shipping_status', 'processing')->count(),
            'shipped'    => Order::where('shipping_status', 'shipped')->count(),
            'delivered'  => Order::where('shipping_status', 'delivered')->count(),
            'cancelled'  => Order::where('shipping_status', 'cancelled')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'counts'));
    }

    public function show(int $id): View
    {
        $order = Order::with(['items.product', 'customer.addresses', 'payments', 'shipments'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->input('status');

        $order->status = $newStatus;
        if ($request->filled('payment_status')) {
            $order->payment_status = $request->input('payment_status');
        }
        if ($request->filled('notes')) {
            $order->notes = $request->input('notes');
        }
        $order->save();

        AuditLog::log('order.status_update', 'order', $order->id, "Changed order #{$order->order_number} status from {$oldStatus} to {$newStatus}");

        // Notify client on WhatsApp if status changed
        if ($oldStatus !== $newStatus) {
            try {
                app(\App\Services\WhatsAppNotificationService::class)->sendStatusUpdate($order, $newStatus);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('WhatsApp status update notification error: ' . $e->getMessage());
            }
        }

        return back()->with('success', "Order #{$order->order_number} status updated to " . strtoupper($newStatus));
    }
}
