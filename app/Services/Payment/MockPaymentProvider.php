<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Exceptions\PaymentTimeoutException;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProvider
{
    /**
     * Create a payment intent for the given amount.
     */
    public function createIntent(int $amountMinor, string $currency, array $metadata = []): array
    {
        return [
            'provider_intent_id' => 'pi_mock_' . Str::random(24),
            'client_secret'      => 'secret_mock_' . Str::random(24),
            'status'             => 'requires_payment_method',
        ];
    }

    /**
     * Verify and process an inbound webhook payload from the provider.
     */
    public function handleWebhook(string $rawPayload, string $signature): array
    {
        // Mock successful webhook parse
        return [
            'event_type'          => 'payment_intent.succeeded',
            'provider_payment_id' => 'pi_mock_' . Str::random(24),
            'status'              => 'succeeded',
            'amount_minor'        => 0, // In reality, we'd parse this from the payload
        ];
    }

    /**
     * Issue a full or partial refund.
     */
    public function refund(string $providerPaymentId, ?int $amountMinor = null): array
    {
        return [
            'refund_id' => 're_mock_' . Str::random(24),
            'status'    => 'succeeded',
        ];
    }

    /**
     * Synchronous charge (mocking a successful payment token capture).
     * Used if the checkout flow captures payment immediately instead of using Intents.
     */
    public function charge(int $amountMinor, string $currency, string $token, string $idempotencyKey, array $metadata = []): array
    {
        // tok_timeout simulates a network timeout / process kill after the request was sent.
        // The provider may or may not have processed the charge — state is UNKNOWN.
        // MUST throw PaymentTimeoutException, never return success/failure.
        if ($token === 'tok_timeout') {
            throw new PaymentTimeoutException();
        }

        if ($token === 'tok_fail') {
            return [
                'success'        => false,
                'transaction_id' => null,
                'message'        => 'Payment declined by mock provider.',
            ];
        }

        return [
            'success'        => true,
            'transaction_id' => 'ch_mock_' . Str::random(24),
            'message'        => 'Mock payment successful.',
        ];
    }

    /**
     * Verify the state of an unknown payment using its idempotency key.
     */
    public function verifyPayment(string $idempotencyKey): ?array
    {
        // If the idempotency key contains 'fail', simulate a failed/not-found charge.
        // Otherwise simulate a successful charge discovery.
        if (str_contains($idempotencyKey, 'fail')) {
            return [
                'status' => 'failed',
                'transaction_id' => null,
                'amount_minor' => 0,
            ];
        }

        if (str_contains($idempotencyKey, 'notfound')) {
            return null; // Simulate completely missing from provider
        }

        return [
            'status' => 'succeeded',
            'transaction_id' => 'ch_mock_reconciled_' . Str::random(12),
            'amount_minor' => 1000,
        ];
    }
}
