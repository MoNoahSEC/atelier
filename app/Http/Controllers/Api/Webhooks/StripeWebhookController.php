<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\StripePaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripePaymentProvider $provider)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        try {
            $event = $provider->handleWebhook($payload, $signature);
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Error: ' . $e->getMessage());
            return response()->json(['success' => true]);
        }

        $type = $event['type'] ?? '';
        $data = $event['data'] ?? null;

        if (!$data || !isset($data->id)) {
            return response()->json(['success' => true]);
        }

        $order = Order::where('provider_ref', $data->id)->first();
        if (!$order) {
            return response()->json(['success' => true]);
        }

        if ($type === 'payment_intent.succeeded') {
            $order->update(['payment_status' => 'paid']);
            if (class_exists(Payment::class)) {
                Payment::updateOrCreate(
                    ['provider_ref' => $data->id],
                    ['status' => 'completed', 'order_id' => $order->id]
                );
            }
        } elseif ($type === 'payment_intent.payment_failed') {
            $order->update(['payment_status' => 'failed']);
        }

        return response()->json(['success' => true]);
    }
}
