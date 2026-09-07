<?php

declare(strict_types=1);

namespace App\Services\Tax;

use App\Contracts\TaxCalculator;

/**
 * Flat-rate tax calculator.
 *
 * A simple initial implementation that applies a configurable flat rate.
 * Replace or extend by binding a different TaxCalculator in AppServiceProvider.
 */
final class FlatRateTaxCalculator implements TaxCalculator
{
    public function calculate(
        int $subtotalMinor,
        string $currency,
        array $destinationAddress,
        array $lineItems = []
    ): array {
        // Rate stored in config as a float (e.g. 0.15 = 15%) only for calculation purposes;
        // result is rounded to integer minor units.
        $rate = (float) config('commerce.tax.flat_rate', 0.0);

        $taxMinor = (int) round($subtotalMinor * $rate);

        return [
            'tax_minor'    => $taxMinor,
            'breakdown'    => [['description' => 'VAT', 'rate' => $rate, 'amount_minor' => $taxMinor]],
            'tax_inclusive' => (bool) config('commerce.tax.inclusive', false),
        ];
    }
}
