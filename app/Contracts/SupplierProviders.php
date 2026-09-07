<?php

declare(strict_types=1);

namespace App\Contracts;

interface SupplierCatalogProvider
{
    /**
     * Search and retrieve product listings from the supplier catalog.
     *
     * @param  string $query
     * @param  array  $filters  e.g. ['category' => '...', 'min_price' => 100]
     * @param  int    $limit
     * @return array<int, array{supplier_product_id: string, title: string, price_minor: int, currency: string}>
     */
    public function search(string $query, array $filters = [], int $limit = 50): array;

    /**
     * Fetch full product details from the supplier.
     *
     * @param  string $supplierProductId
     * @return array{supplier_product_id: string, title: string, variants: array, ...}
     */
    public function getProduct(string $supplierProductId): array;
}

interface SupplierInventoryProvider
{
    /**
     * Get real-time stock for a set of supplier SKUs.
     *
     * @param  string[] $supplierSkus
     * @return array<string, int>  Map of supplier_sku => quantity
     */
    public function getStock(array $supplierSkus): array;
}

interface SupplierOrderProvider
{
    /**
     * Place a dropship order with the supplier.
     *
     * @param  int   $orderId           Internal order ID for idempotency.
     * @param  array $lineItems         Supplier SKUs + quantities.
     * @param  array $shippingAddress   Normalized destination address.
     * @return array{supplier_order_id: string, estimated_ship_date: ?string}
     */
    public function placeOrder(int $orderId, array $lineItems, array $shippingAddress): array;

    /**
     * Cancel a previously placed supplier order, if the supplier allows it.
     *
     * @param  string $supplierOrderId
     * @return bool
     */
    public function cancelOrder(string $supplierOrderId): bool;
}

interface SupplierShipmentProvider
{
    /**
     * Retrieve shipment/tracking updates for a supplier order.
     *
     * @param  string $supplierOrderId
     * @return array{tracking_number: ?string, carrier: ?string, status: string, events: array}
     */
    public function getShipmentStatus(string $supplierOrderId): array;
}
