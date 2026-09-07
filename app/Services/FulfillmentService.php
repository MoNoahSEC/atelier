<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    /**
     * Valid state transitions for fulfillment_status.
     */
    private const FULFILLMENT_TRANSITIONS = [
        'unfulfilled'         => ['partially_fulfilled', 'fulfilled', 'cancelled'],
        'partially_fulfilled' => ['fulfilled', 'cancelled'],
        'fulfilled'           => [],
        'cancelled'           => [],
    ];

    /**
     * Valid state transitions for shipping_status.
     */
    private const SHIPPING_TRANSITIONS = [
        'pending'    => ['processing', 'shipped', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['in_transit', 'delivered', 'returned'],
        'in_transit' => ['delivered', 'returned'],
        'delivered'  => [],
        'returned'   => [],
        'cancelled'  => [],
    ];

    /**
     * Process a fulfillment update idempotently.
     *
     * @param  array{
     *   order_id?: int,
     *   external_order_id?: string,
     *   fulfillment_status: string,
     *   shipping_status?: string,
     *   tracking_number?: string,
     *   carrier?: string,
     *   idempotency_key: string
     * } $data
     */
    public function processFulfillment(array $data, ?string $actorType = null, ?int $actorId = null): Order
    {
        return DB::transaction(function () use ($data, $actorType, $actorId): Order {
            // Resolve order with lock to prevent concurrent updates
            $order = $this->resolveOrderWithLock($data);

            // Check idempotency — if this key was already processed, return order
            try {
                DB::table('webhook_idempotencies')->insert([
                    'key' => $data['idempotency_key'],
                    'provider' => 'fulfillment',
                    'payload' => json_encode($data),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // If unique constraint fails, it means we already processed this webhook
                if ($e->getCode() === '23000' || $e->getCode() === '23505' || str_contains($e->getMessage(), 'UNIQUE')) {
                    Log::info('Fulfillment idempotency hit', [
                        'order_id'        => $order->id,
                        'idempotency_key' => $data['idempotency_key'],
                    ]);
                    return $order;
                }
                throw $e;
            }

            // Validate fulfillment state transition
            $this->assertValidFulfillmentTransition($order, $data['fulfillment_status']);

            if (isset($data['shipping_status'])) {
                $this->assertValidShippingTransition($order, $data['shipping_status']);
            }

            $changes = [];

            // Apply fulfillment_status change
            if ($order->fulfillment_status !== $data['fulfillment_status']) {
                $changes['fulfillment_status'] = [
                    'from' => $order->fulfillment_status,
                    'to'   => $data['fulfillment_status'],
                ];
                $order->fulfillment_status = $data['fulfillment_status'];
            }

            // Apply shipping_status change
            if (isset($data['shipping_status']) && $order->shipping_status !== $data['shipping_status']) {
                $changes['shipping_status'] = [
                    'from' => $order->shipping_status,
                    'to'   => $data['shipping_status'],
                ];
                $order->shipping_status = $data['shipping_status'];
            }

            // Update tracking
            if (isset($data['tracking_number'])) {
                $order->tracking_number = $data['tracking_number'];
            }
            if (isset($data['carrier'])) {
                $order->carrier = $data['carrier'];
            }

            $order->save();

            // Create/update shipment record
            if (isset($data['tracking_number'], $data['shipping_status'])) {
                $this->upsertShipment($order, $data);
            }

            // Record audit log
            AuditLog::create([
                'actor_type'   => $actorType,
                'actor_id'     => $actorId,
                'action'       => 'fulfillment.updated',
                'entity_type'  => 'order',
                'entity_id'    => $order->id,
                'changes_json' => array_merge($changes, [
                    'idempotency_key' => $data['idempotency_key'],
                ]),
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
            ]);

            Log::info('Fulfillment processed', [
                'order_id'           => $order->id,
                'order_number'       => $order->order_number,
                'fulfillment_status' => $order->fulfillment_status,
                'shipping_status'    => $order->shipping_status,
                'idempotency_key'    => $data['idempotency_key'],
            ]);

            return $order;
        });
    }

    /**
     * Lock and resolve order by id or external_order_id.
     */
    private function resolveOrderWithLock(array $data): Order
    {
        $query = Order::lockForUpdate();

        if (isset($data['order_id'])) {
            $order = $query->find($data['order_id']);
        } else {
            $order = $query->where('external_order_id', $data['external_order_id'])->first();
        }

        if ($order === null) {
            $identifier = $data['order_id'] ?? $data['external_order_id'];
            throw ValidationException::withMessages([
                'order_id' => "Order not found: {$identifier}",
            ]);
        }

        return $order;
    }

    /**
     * Obsolete: replaced by DB unique constraint insert.
     */
    private function alreadyProcessed(int $orderId, string $idempotencyKey): bool
    {
        return false;
    }

    /**
     * Assert a fulfillment status transition is valid.
     */
    private function assertValidFulfillmentTransition(Order $order, string $newStatus): void
    {
        $allowed = self::FULFILLMENT_TRANSITIONS[$order->fulfillment_status] ?? [];

        if ($newStatus === $order->fulfillment_status) {
            return; // No-op transition is allowed (idempotent)
        }

        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'fulfillment_status' => "Cannot transition fulfillment from '{$order->fulfillment_status}' to '{$newStatus}'.",
            ]);
        }
    }

    /**
     * Assert a shipping status transition is valid.
     */
    private function assertValidShippingTransition(Order $order, string $newStatus): void
    {
        $allowed = self::SHIPPING_TRANSITIONS[$order->shipping_status] ?? [];

        if ($newStatus === $order->shipping_status) {
            return;
        }

        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'shipping_status' => "Cannot transition shipping from '{$order->shipping_status}' to '{$newStatus}'.",
            ]);
        }
    }

    /**
     * Create or update the associated shipment record.
     */
    private function upsertShipment(Order $order, array $data): void
    {
        $shipment = Shipment::where('order_id', $order->id)->latest()->first();

        $attrs = [
            'tracking_number' => $data['tracking_number'] ?? null,
            'carrier'         => $data['carrier'] ?? null,
            'status'          => $data['shipping_status'] ?? 'shipped',
        ];

        if (($data['shipping_status'] ?? null) === 'shipped') {
            $attrs['shipped_at'] = now();
        }
        if (($data['shipping_status'] ?? null) === 'delivered') {
            $attrs['delivered_at'] = now();
        }

        if ($shipment !== null) {
            $shipment->update($attrs);
        } else {
            Shipment::create(array_merge(['order_id' => $order->id], $attrs));
        }
    }
}
