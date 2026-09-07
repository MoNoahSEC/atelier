<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::withCount(['orders', 'addresses'])
            ->with('addresses')
            ->withSum(['orders as total_spent_minor' => fn($q) => $q->where('payment_status', 'paid')], 'total_amount_minor')
            ->when($request->search, fn($q) => $q->where('email', 'like', "%{$request->search}%")
                ->orWhere('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at');

        $customers = $query->paginate($request->integer('per_page', 20));

        $data = collect($customers->items())->map(function ($c) {
            $primaryAddress = $c->addresses->first();
            return [
                'id' => $c->id,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'status' => $c->status,
                'created_at' => $c->created_at,
                'orders_count' => $c->orders_count,
                'addresses_count' => $c->addresses_count,
                'total_spent_minor' => $c->total_spent_minor,
                'city' => $primaryAddress?->city,
                'country' => $primaryAddress?->country,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'        => $customers->total(),
                'per_page'     => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
            ],
        ]);
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $customers = Customer::withCount('orders')
            ->withSum(['orders as total_spent_minor' => fn($q) => $q->where('payment_status', 'paid')], 'total_amount_minor')
            ->with('addresses')
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=customers.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Orders Count', 'Total Spent', 'City', 'Country', 'Joined Date']);

            foreach ($customers as $c) {
                $address = $c->addresses->first();
                $totalSpent = $c->total_spent_minor ? ($c->total_spent_minor / 100) : 0;
                $name = trim($c->first_name . ' ' . $c->last_name);
                
                fputcsv($file, [
                    $c->id,
                    $name,
                    $c->email,
                    $c->phone ?? '',
                    $c->orders_count ?? 0,
                    $totalSpent,
                    $address?->city ?? '',
                    $address?->country ?? '',
                    $c->created_at?->format('Y-m-d') ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['orders', 'addresses'])->findOrFail($id);

        // Find linked User account by email (if any)
        $userAccount = User::where('email', $customer->email)->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $customer->id,
                'email'      => $customer->email,
                'first_name' => $customer->first_name,
                'last_name'  => $customer->last_name,
                'phone'      => $customer->phone,
                'status'     => $customer->status,
                'created_at' => $customer->created_at,
                'is_banned'  => $userAccount?->is_banned ?? false,
                'ban_reason' => $userAccount?->ban_reason,
                'banned_at'  => $userAccount?->banned_at,
                'orders'     => $customer->orders->map(fn($o) => [
                    'id'                 => $o->id,
                    'order_number'       => $o->order_number,
                    'total_amount_minor' => $o->total_amount_minor,
                    'currency'           => $o->currency,
                    'payment_status'     => $o->payment_status,
                    'fulfillment_status' => $o->fulfillment_status,
                    'created_at'         => $o->created_at,
                ]),
                'addresses'  => $customer->addresses,
            ],
        ]);
    }

    /**
     * Ban a customer account (prevents login).
     * Targets the User record linked by email.
     */
    public function ban(int $id, Request $request): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        // Prevent banning admin accounts
        $user = User::where('email', $customer->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'This customer does not have a registered account.',
            ], 422);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts cannot be banned.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user->update([
            'is_banned'  => true,
            'banned_at'  => now(),
            'ban_reason' => $validated['reason'] ?? null,
        ]);

        // Revoke all tokens so they're logged out immediately
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => "Customer {$customer->email} has been banned.",
            'data'    => [
                'is_banned'  => true,
                'banned_at'  => $user->banned_at,
                'ban_reason' => $user->ban_reason,
            ],
        ]);
    }

    /**
     * Unban a customer account.
     */
    public function unban(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $user = User::where('email', $customer->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No registered account found for this customer.',
            ], 422);
        }

        $user->update([
            'is_banned'  => false,
            'banned_at'  => null,
            'ban_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Customer {$customer->email} has been unbanned.",
        ]);
    }
}
