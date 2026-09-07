<?php

declare(strict_types=1);

namespace App\Contracts;

interface PaymentProvider
{
    /**
     * Create a payment intent for the given amount.
     *
     * @param  int    $amountMinor  Amount in the smallest currency unit (e.g. cents).
     * @param  string $currency     ISO 4217 currency code.
     * @param  array  $metadata     Arbitrary provider-safe metadata (no secrets).
     * @return array{provider_intent_id: string, client_secret: string, status: string}
     */
    public function createIntent(int $amountMinor, string $currency, array $metadata = []): array;

    /**
     * Verify and process an inbound webhook payload from the provider.
     *
     * @param  string $rawPayload   Raw request body.
     * @param  string $signature    Provider signature header value.
     * @return array{event_type: string, provider_payment_id: string, status: string, amount_minor: int}
     */
    public function handleWebhook(string $rawPayload, string $signature): array;

    /**
     * Issue a full or partial refund.
     *
     * @param  string   $providerPaymentId  The provider's payment/charge ID.
     * @param  int|null $amountMinor        Null for full refund.
     * @return array{refund_id: string, status: string}
     */
    public function refund(string $providerPaymentId, ?int $amountMinor = null): array;

    /**
     * Synchronously charge a specific amount using a token.
     *
     * @param int $amountMinor The amount in minor units (e.g., cents).
     * @param string $currency The currency code (e.g., 'USD').
     * @param string $token The payment token or source.
     * @param string $idempotencyKey A unique key to prevent duplicate charges.
     * @param array $metadata Optional metadata to pass to the provider.
     * @return array{success: bool, transaction_id: ?string, message: string}
     */
    public function charge(int $amountMinor, string $currency, string $token, string $idempotencyKey, array $metadata = []): array;

    /**
     * Verify the state of an unknown payment using its idempotency key.
     *
     * @param string $idempotencyKey The unique key generated for the payment attempt.
     * @return array{status: string, transaction_id: ?string, amount_minor: int}|null Returns null if not found.
     */
    public function verifyPayment(string $idempotencyKey): ?array;
}
