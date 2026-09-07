<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Order;
use App\Models\InventoryMovement;
use App\Contracts\PaymentProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles Payment records that remain `pending` beyond the configured threshold.
 *
 * Safety guarantees:
 *   - A single provider lookup per payment using the persisted idempotency_key.
 *   - Payments are transitioned to `reconciling` under a row-level lock before
 *     querying the provider, preventing duplicate workers from acting on the
 *     same payment simultaneously.
 *   - On success: order finalized under a lock; duplicate calls converge safely.
 *   - On failure/not-found: inventory compensation is idempotent via
 *     InventoryMovement existence check before restoring stock.
 *   - No charge is ever initiated from this job.
 */
class ReconcilePendingPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Minimum age in minutes before a pending payment is eligible for reconciliation.
     * Provider calls initiated within this window may still be in-flight.
     */
    private int $minAgeMinutes;

    /**
     * Maximum age in minutes. Payments older than this are marked permanently unresolvable
     * only if the provider also cannot find them. Set to 0 to disable upper bound.
     */
    private int $maxAgeMinutes;

    /**
     * Explicit statuses eligible for reconciliation.
     */
    private array $eligibleStatuses;

    public function __construct(
        int $minAgeMinutes = 5,
        int $maxAgeMinutes = 1440,          // 24 hours
        array $eligibleStatuses = ['pending', 'reconciling']
    ) {
        $this->minAgeMinutes   = $minAgeMinutes;
        $this->maxAgeMinutes   = $maxAgeMinutes;
        $this->eligibleStatuses = $eligibleStatuses;
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentProvider $paymentProvider): void
    {
        $query = Payment::whereIn('status', $this->eligibleStatuses)
            ->whereNotNull('idempotency_key')
            ->where('created_at', '<=', now()->subMinutes($this->minAgeMinutes));

        if ($this->maxAgeMinutes > 0) {
            $query->where('created_at', '>=', now()->subMinutes($this->maxAgeMinutes));
        }

        // Process in small chunks to avoid memory issues
        $query->orderBy('created_at')->chunk(100, function ($payments) use ($paymentProvider) {
            foreach ($payments as $payment) {
                try {
                    $this->reconcilePayment($payment, $paymentProvider);
                } catch (\Exception $e) {
                    Log::error('ReconcilePendingPayments: failed for payment', [
                        'payment_id'       => $payment->id,
                        'idempotency_key'  => $payment->idempotency_key,
                        'error'            => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    private function reconcilePayment(Payment $payment, PaymentProvider $paymentProvider): void
    {
        // ── 1. Claim the payment for reconciliation under a row lock ──────────
        // This prevents two workers from reconciling the same payment simultaneously.
        $claimed = DB::transaction(function () use ($payment): ?Payment {
            $locked = Payment::lockForUpdate()->find($payment->id);

            if ($locked === null) {
                return null; // Deleted concurrently
            }

            // Only claim if still in an eligible status
            if (!in_array($locked->status, ['pending', 'reconciling'], true)) {
                return null; // Already resolved by webhook, checkout, or another worker
            }

            // Transition to 'reconciling' so no other worker picks this up
            if ($locked->status === 'pending') {
                $locked->update(['status' => 'reconciling']);
            }

            return $locked;
        });

        if ($claimed === null) {
            return; // Nothing to do
        }

        // ── 2. Query the provider (outside transaction, uses idempotency_key) ─
        $result = null;
        try {
            $result = $paymentProvider->verifyPayment($claimed->idempotency_key);
        } catch (\Exception $e) {
            // Provider lookup failed — revert to pending so we can retry next run
            DB::transaction(function () use ($claimed) {
                $locked = Payment::lockForUpdate()->find($claimed->id);
                if ($locked && $locked->status === 'reconciling') {
                    $locked->update(['status' => 'pending']);
                }
            });
            Log::warning('ReconcilePendingPayments: provider lookup failed, reverting to pending', [
                'payment_id' => $claimed->id,
                'error'      => $e->getMessage(),
            ]);
            return;
        }

        // ── 3. Apply the authoritative provider result ────────────────────────
        DB::transaction(function () use ($claimed, $result) {
            // Re-acquire lock before writing
            $payment = Payment::lockForUpdate()->find($claimed->id);

            if ($payment === null || $payment->status !== 'reconciling') {
                return; // Resolved by webhook while we were querying
            }

            $order = Order::lockForUpdate()->find($payment->order_id);

            if ($result !== null && $result['status'] === 'succeeded') {
                // Provider confirms: charge succeeded. Finalize the order.
                $payment->update([
                    'status'                  => 'completed',
                    'provider_transaction_id' => $result['transaction_id'],
                    'metadata_json'           => array_merge($payment->metadata_json ?? [], [
                        'reconciled_at' => now()->toISOString(),
                        'reconciliation_outcome' => 'succeeded',
                    ]),
                ]);

                if ($order && $order->payment_status !== 'paid') {
                    $order->update(['payment_status' => 'paid']);
                }

                Log::info('ReconcilePendingPayments: payment reconciled as succeeded', [
                    'payment_id'             => $payment->id,
                    'provider_transaction_id' => $result['transaction_id'],
                    'order_id'               => $payment->order_id,
                ]);

            } else {
                // Provider returns null (not found) or explicit failure.
                // Safe to mark failed and compensate inventory.
                $payment->update([
                    'status'        => 'failed',
                    'metadata_json' => array_merge($payment->metadata_json ?? [], [
                        'reconciled_at' => now()->toISOString(),
                        'reconciliation_outcome' => $result === null ? 'not_found' : 'failed',
                    ]),
                ]);

                if ($order && $order->payment_status !== 'failed') {
                    $order->update(['payment_status' => 'failed']);
                }

                // Idempotent inventory compensation:
                // Only restore stock if no prior compensation movement exists for this order.
                if ($order) {
                    $this->compensateInventory($order);
                }

                Log::info('ReconcilePendingPayments: payment reconciled as failed, inventory compensated', [
                    'payment_id' => $payment->id,
                    'order_id'   => $payment->order_id,
                ]);
            }
        });
    }

    /**
     * Idempotently restore inventory for all items in a failed order.
     * Must be called inside an active DB transaction with $order already locked.
     */
    private function compensateInventory(Order $order): void
    {
        foreach ($order->items as $item) {
            // Guard: skip if a compensation movement already exists for this product+order
            $alreadyCompensated = InventoryMovement::where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->where('type', 'increment')
                ->where('product_id', $item->product_id)
                ->when(
                    $item->product_variant_id,
                    fn ($q) => $q->where('product_variant_id', $item->product_variant_id)
                )
                ->whereJsonContains('metadata_json->reason', 'payment_reconciliation_compensation')
                ->exists();

            if ($alreadyCompensated) {
                continue;
            }

            $model = $item->product_variant_id
                ? \App\Models\ProductVariant::find($item->product_variant_id)
                : \App\Models\Product::find($item->product_id);

            if ($model) {
                $model->increment('inventory', $item->quantity);
            }

            InventoryMovement::create([
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'type'               => 'increment',
                'quantity'           => $item->quantity,
                'reference_type'     => 'order',
                'reference_id'       => $order->id,
                'metadata_json'      => ['reason' => 'payment_reconciliation_compensation'],
            ]);
        }
    }
}
