<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function resolve(string $method): PaymentProvider
    {
        $enabledMethods = config('payment.enabled_methods', []);
        
        if (!in_array($method, $enabledMethods, true)) {
            throw new InvalidArgumentException("Payment method [{$method}] is not enabled or not supported.");
        }

        return match ($method) {
            'stripe' => app(StripePaymentProvider::class),
            'paymob' => app(PaymobPaymentProvider::class),
            'cod' => app(CodPaymentProvider::class),
            default => throw new InvalidArgumentException("Payment method [{$method}] is not supported."),
        };
    }

    public function enabledMethods(): array
    {
        $enabled = config('payment.enabled_methods', []);
        $methods = [];

        foreach ($enabled as $method) {
            $methods[] = match ($method) {
                'stripe' => [
                    'id' => 'stripe',
                    'label' => 'Credit / Debit Card',
                    'description' => 'Pay securely with your Visa or Mastercard via Stripe',
                ],
                'paymob' => [
                    'id' => 'paymob',
                    'label' => 'Online Payment (Paymob)',
                    'description' => 'Pay using cards or mobile wallets via Paymob',
                ],
                'cod' => [
                    'id' => 'cod',
                    'label' => 'Cash on Delivery',
                    'description' => 'Pay in cash when your order is delivered',
                ],
                default => null,
            };
        }

        return array_filter($methods);
    }
}
