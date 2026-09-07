<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a payment provider call completes without a definitive success or failure.
 *
 * This MUST never trigger inventory compensation or payment failure marking.
 * The Payment record remains in `pending` status to be resolved by the
 * ReconcilePendingPayments job using the persisted idempotency_key.
 */
class PaymentTimeoutException extends RuntimeException
{
    public function __construct(string $message = 'Provider connection timed out. Payment state unknown.')
    {
        parent::__construct($message);
    }
}
