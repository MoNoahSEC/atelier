<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Retrieve orders pending external fulfillment.
     * Returns only paid + unfulfilled orders with minimal fields.
     */
    public function getPendingOrders(array $filters = [], int $perPage = 100): CursorPaginator
    {
        $query = Order::pendingFulfillment()
            ->with('items')
            ->select([
                'id', 'order_number', 'external_order_id',
                'customer_email', 'currency', 'total_amount_minor',
                'fulfillment_status', 'payment_status', 'created_at',
            ])
            ->orderBy('created_at')
            ->orderBy('id');

        if (isset($filters['currency'])) {
            $query->where('currency', strtoupper($filters['currency']));
        }

        if (isset($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        return $query->cursorPaginate($perPage);
    }

    /**
     * Generate a unique order number.
     */
    public function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . strtoupper(substr(uniqid('', true), -8));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
