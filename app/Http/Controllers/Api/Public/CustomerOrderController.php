<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    /**
     * List orders for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();
        $orders = Order::where('customer_id', $customer->id)
            ->with(['items', 'payment'])
            ->orderByDesc('created_at')
            ->paginate(10);
            
        return response()->json($orders);
    }

    /**
     * Show single order for authenticated customer.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $customer = $request->user();
        $order = Order::where('customer_id', $customer->id)
            ->with(['items', 'payment'])
            ->findOrFail($id);
            
        return response()->json($order);
    }

    /**
     * Track an order publicly by order number and phone/email (guest tracking).
     */
    public function trackGuestOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:50',
            'contact'      => 'required|string|max:255',
        ]);

        $orderNum = trim($validated['order_number']);
        $contact = mb_strtolower(trim($validated['contact']));

        $order = Order::where('order_number', $orderNum)
            ->with(['items', 'payment'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'No order found with the provided order number.'
            ], 404);
        }

        // Verify contact matches customer email or phone
        $customer = Customer::find($order->customer_id);
        $matches = false;

        if (strcasecmp($order->customer_email, $contact) === 0) {
            $matches = true;
        } elseif ($customer && ($customer->phone && str_contains($customer->phone, $contact) || str_contains($contact, $customer->phone))) {
            $matches = true;
        } elseif (isset($order->shipping_address['phone']) && str_contains($order->shipping_address['phone'], $contact)) {
            $matches = true;
        }

        // If contact is only the phone number digits
        $cleanContact = preg_replace('/[^0-9]/', '', $contact);
        if (!$matches && $cleanContact && strlen($cleanContact) >= 8) {
            if ($customer && $customer->phone && str_contains(preg_replace('/[^0-9]/', '', $customer->phone), $cleanContact)) {
                $matches = true;
            }
        }

        if (!$matches) {
            return response()->json([
                'success' => false,
                'message' => 'The phone number or email does not match this order.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    /**
     * Confirm payment for Paymob gateway redirect simulation.
     */
    public function confirmPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number'   => 'required|string|max:50',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $order = Order::where('order_number', $validated['order_number'])->firstOrFail();
        $order->update([
            'payment_status' => 'paid',
            'payment_method' => $validated['payment_method'] ?? 'paymob',
        ]);

        // Update latest payment record
        if ($order->payment) {
            $order->payment->update([
                'status' => 'captured',
            ]);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Payment confirmed.',
            'order_number' => $order->order_number,
        ]);
    }
}
