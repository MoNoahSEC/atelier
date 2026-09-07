<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use Illuminate\Support\Str;

class FlatRateShippingProvider implements ShippingProvider
{
    /**
     * Fetch available shipping rates for a given shipment request.
     */
    public function getRates(array $origin, array $destination, array $parcels): array
    {
        // Simple mock logic: if international (not US), $25. Otherwise $10.
        $isDomestic = ($destination['country_code'] ?? 'US') === 'US';
        $amount = $isDomestic ? 1000 : 2500; // $10 or $25
        
        // Also provide a free option if order subtotal (which we don't know here strictly)
        // For now just return standard rate and express rate
        return [
            [
                'service_code' => 'standard',
                'name' => 'Standard Shipping',
                'amount_minor' => $amount,
                'currency' => 'USD',
                'estimated_days' => 5,
            ],
            [
                'service_code' => 'express',
                'name' => 'Express Shipping',
                'amount_minor' => $amount + 1500,
                'currency' => 'USD',
                'estimated_days' => 2,
            ]
        ];
    }

    /**
     * Create a shipment and obtain a tracking number.
     */
    public function createShipment(int $orderId, array $destination, array $parcels, string $serviceCode): array
    {
        return [
            'tracking_number' => 'TRK' . strtoupper(Str::random(10)),
            'label_url' => 'https://example.com/label/' . Str::random(10) . '.pdf',
            'provider_shipment_id' => 'shp_' . Str::random(16),
        ];
    }

    /**
     * Get live tracking events for a shipment.
     */
    public function getTrackingEvents(string $trackingNumber): array
    {
        return [
            [
                'status' => 'label_created',
                'description' => 'Shipping label created',
                'occurred_at' => now()->toIso8601String(),
            ]
        ];
    }
}
