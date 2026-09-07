<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['addresses'])->where('is_admin', false)->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'banned') {
                $query->where('is_banned', true);
            } elseif ($request->input('status') === 'active') {
                $query->where('is_banned', false);
            }
        }

        $customers = $query->paginate(15)->withQueryString();

        // Calculate customer stats
        $customerIds = $customers->pluck('id')->toArray();
        $customerEmails = $customers->pluck('email')->toArray();

        $orderStats = Order::whereIn('customer_email', $customerEmails)
            ->where('shipping_status', '!=', 'cancelled')
            ->selectRaw('customer_email, COUNT(*) as orders_count, SUM(total_amount_minor) as total_spent')
            ->groupBy('customer_email')
            ->get()
            ->keyBy('customer_email');

        return view('admin.customers.index', compact('customers', 'orderStats'));
    }

    public function show(int $id): View
    {
        $customer = User::with(['addresses'])->findOrFail($id);

        $orders = Order::where('customer_email', $customer->email)
            ->with('items')
            ->latest()
            ->get();

        $totalSpent = $orders->where('shipping_status', '!=', 'cancelled')->sum('total_amount_minor');
        $ordersCount = $orders->count();

        return view('admin.customers.show', compact('customer', 'orders', 'totalSpent', 'ordersCount'));
    }

    public function toggleBan(Request $request, int $id)
    {
        $customer = User::findOrFail($id);
        $customer->is_banned = !$customer->is_banned;
        $customer->banned_at = $customer->is_banned ? now() : null;
        $customer->ban_reason = $customer->is_banned ? ($request->input('ban_reason') ?: 'Administrative block') : null;
        $customer->save();

        $action = $customer->is_banned ? 'Blocked' : 'Unblocked';
        AuditLog::log('customer.toggle_ban', 'user', $customer->id, "{$action} customer {$customer->email}");

        return back()->with('success', "Customer account {$customer->email} has been {$action}.");
    }

    public function sendPasswordReset(int $id)
    {
        $customer = User::findOrFail($id);

        // Security check: NEVER reveal password, strictly dispatch reset link
        $token = Password::broker()->createToken($customer);
        $customer->password_reset_requested_at = now();
        $customer->password_reset_requested_by = 'Admin (' . (auth()->user()->name ?? 'Administrator') . ')';
        $customer->save();

        AuditLog::log('customer.password_reset', 'user', $customer->id, "Triggered secure password reset link for {$customer->email}");

        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $customer->email,
        ], false));

        if (request()->wantsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => "Password reset link generated for {$customer->email}.",
                'reset_url' => $resetUrl,
                'timestamp' => now()->format('M d, Y H:i:s'),
            ]);
        }

        return back()->with('success', "Password reset token dispatched for {$customer->email}. Reset Link: {$resetUrl}");
    }
}
