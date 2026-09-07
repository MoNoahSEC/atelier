<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;

class NotificationService
{
    public static function notifyNewOrder(Order $order): void
    {
        AdminNotification::create([
            'type' => 'new_order',
            'title' => 'New Order Received',
            'message' => "Order #{$order->order_number} has been placed.",
            'data_json' => ['order_id' => $order->id, 'order_number' => $order->order_number],
        ]);
    }

    public static function notifyPaymentFailed(Order $order): void
    {
        AdminNotification::create([
            'type' => 'payment_failed',
            'title' => 'Payment Failed',
            'message' => "Payment failed for Order #{$order->order_number}.",
            'data_json' => ['order_id' => $order->id, 'order_number' => $order->order_number],
        ]);
    }

    public static function notifyLowStock(Product $product): void
    {
        AdminNotification::create([
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => "Product '{$product->title}' (SKU: {$product->sku}) is running low on stock.",
            'data_json' => ['product_id' => $product->id, 'sku' => $product->sku, 'inventory' => $product->inventory],
        ]);
    }

    public static function notifyNewCustomer(Customer $customer): void
    {
        AdminNotification::create([
            'type' => 'new_customer',
            'title' => 'New Customer Registration',
            'message' => "A new customer ({$customer->email}) has registered.",
            'data_json' => ['customer_id' => $customer->id, 'email' => $customer->email],
        ]);
    }

    public static function notifyOrderCancelled(Order $order): void
    {
        AdminNotification::create([
            'type' => 'order_cancelled',
            'title' => 'Order Cancelled',
            'message' => "Order #{$order->order_number} has been cancelled.",
            'data_json' => ['order_id' => $order->id, 'order_number' => $order->order_number],
        ]);
    }
}
