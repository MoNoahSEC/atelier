<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number'       => 'ORD-' . strtoupper(Str::random(8)),
            'customer_id'        => Customer::factory(),
            'customer_email'     => $this->faker->safeEmail(),
            'currency'           => 'USD',
            'subtotal_minor'     => 1000,
            'shipping_minor'     => 0,
            'tax_minor'          => 0,
            'discount_minor'     => 0,
            'total_amount_minor' => 1000,
            'payment_status'     => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'shipping_status'    => 'pending',
        ];
    }
}
