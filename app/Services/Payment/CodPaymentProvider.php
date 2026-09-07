<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use Illuminate\Support\Str;

class CodPaymentProvider implements PaymentProvider
{
    public function createIntent(int $amountMinor, string $currency, array $metadata = []): array
    {
        return [
            'provider_intent_id' => 'cod_' . Str::random(16),
            'client_secret' => '',
            'status' => 'cod_pending',
        ];
    }

    public function handleWebhook(string $rawPayload, string $signature): array
    {
        return [];
    }

    public function charge(int $amountMinor, string $currency, string $token, string $idempotencyKey, array $metadata = []): array
    {
        return [
            'success' => true,
            'transaction_id' => 'cod_' . Str::random(16),
            'message' => 'Cash on delivery - payment pending',
        ];
    }

    public function refund(string $providerPaymentId, ?int $amountMinor = null): array
    {
        return [
            'status' => 'manual_refund_required',
        ];
    }

    public function verifyPayment(string $idempotencyKey): ?array
    {
        return null;
    }
}
