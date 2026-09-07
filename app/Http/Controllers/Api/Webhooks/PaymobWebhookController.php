<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\PaymobPaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymobWebhookController extends Controller
{
    public function __invoke(Request $request, PaymobPaymentProvider $provider)
    {
        $payload = $request->getContent();
        $hmac = $request->query('hmac', '');

        try {
            $event = $provider->handleWebhook($payload, $hmac);
        } catch (\Exception $e) {
            Log::error('Paymob Webhook Error: ' . $e->getMessage());
            return response()->json(['success' => true]);
        }

        $merchantOrderId = $event['order_id'] ?? null;
        if (!$merchantOrderId) {
            return response()->json(['success' => true]);
        }

        $order = Order::find($merchantOrderId);
        if (!$order) {
            return response()->json(['success' => true]);
        }

        if ($event['success'] === true && $event['pending'] === false) {
            if ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
            }
        } elseif ($event['success'] === false) {
            $order->update(['payment_status' => 'failed']);
        }

        return response()->json(['success' => true]);
    }
}
