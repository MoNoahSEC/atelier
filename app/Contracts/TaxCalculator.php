<?php

declare(strict_types=1);

namespace App\Contracts;

interface TaxCalculator
{
    /**
     * Calculate tax for a given order context.
     *
     * @param  int    $subtotalMinor   Pre-tax subtotal in smallest unit.
     * @param  string $currency        ISO 4217 currency code.
     * @param  array  $destinationAddress  Normalized address (country_code, region, postal_code).
     * @param  array  $lineItems       Each item with amount_minor, quantity, tax_category.
     * @return array{tax_minor: int, breakdown: array, tax_inclusive: bool}
     */
    public function calculate(
        int $subtotalMinor,
        string $currency,
        array $destinationAddress,
        array $lineItems = []
    ): array;
}
