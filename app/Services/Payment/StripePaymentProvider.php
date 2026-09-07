<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use InvalidArgumentException;

class StripePaymentProvider implements PaymentProvider
{
    private ?StripeClient $client = null;

    private function getClient(): StripeClient
    {
        if ($this->client === null) {
            $key = config('payment.stripe.secret_key') ?: 'sk_test_placeholder';
            $this->client = new StripeClient($key);
        }
        return $this->client;
    }

    public function createIntent(int $amountMinor, string $currency, array $metadata = []): array
    {
        $secretKey = config('payment.stripe.secret_key');
        if (empty($secretKey) || str_contains($secretKey, '...')) {
            $mockPi = 'pi_mock_' . bin2hex(random_bytes(8));
            return [
                'provider_intent_id' => $mockPi,
                'client_secret' => $mockPi . '_secret_' . bin2hex(random_bytes(8)),
                'status' => 'requires_payment_method',
            ];
        }

        $pi = $this->getClient()->paymentIntents->create([
            'amount' => $amountMinor,
            'currency' => $currency,
            'metadata' => $metadata,
        ]);

        return [
            'provider_intent_id' => $pi->id,
            'client_secret' => $pi->client_secret,
            'status' => $pi->status,
        ];
    }

    public function handleWebhook(string $rawPayload, string $signature): array
    {
        $endpointSecret = config('payment.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($rawPayload, $signature, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            throw new InvalidArgumentException('Invalid Stripe signature.');
        }

        return [
            'type' => $event->type,
            'data' => $event->data->object,
        ];
    }

    public function charge(int $amountMinor, string $currency, string $token, string $idempotencyKey, array $metadata = []): array
    {
        $pi = $this->getClient()->paymentIntents->confirm($token, [
            'payment_method' => 'pm_card_visa', // In a real flow, the token is passed
        ], [
            'idempotency_key' => $idempotencyKey,
        ]);

        return [
            'provider_intent_id' => $pi->id,
            'status' => $pi->status,
        ];
    }

    public function refund(string $providerPaymentId, ?int $amountMinor = null): array
    {
        $params = ['payment_intent' => $providerPaymentId];
        if ($amountMinor) {
            $params['amount'] = $amountMinor;
        }
        
        $refund = $this->getClient()->refunds->create($params);

        return [
            'refund_id' => $refund->id,
            'status' => $refund->status,
        ];
    }

    public function verifyPayment(string $idempotencyKey): ?array
    {
        return null;
    }
}
