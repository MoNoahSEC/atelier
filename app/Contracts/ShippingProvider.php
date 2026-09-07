<?php

declare(strict_types=1);

namespace App\Contracts;

interface ShippingProvider
{
    /**
     * Fetch available shipping rates for a given shipment request.
     *
     * @param  array $origin      Normalized origin address.
     * @param  array $destination Normalized destination address.
     * @param  array $parcels     Array of parcel dimensions/weights.
     * @return array<int, array{service_code: string, name: string, amount_minor: int, currency: string, estimated_days: ?int}>
     */
    public function getRates(array $origin, array $destination, array $parcels): array;

    /**
     * Create a shipment and obtain a tracking number.
     *
     * @param  int   $orderId      Internal order ID.
     * @param  array $destination  Normalized destination address.
     * @param  array $parcels      Parcel data.
     * @param  string $serviceCode The chosen service code from getRates().
     * @return array{tracking_number: string, label_url: ?string, provider_shipment_id: string}
     */
    public function createShipment(int $orderId, array $destination, array $parcels, string $serviceCode): array;

    /**
     * Get live tracking events for a shipment.
     *
     * @param  string $trackingNumber
     * @return array<int, array{status: string, description: string, occurred_at: string}>
     */
    public function getTrackingEvents(string $trackingNumber): array;
}
