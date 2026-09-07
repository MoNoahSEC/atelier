<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'email'      => $this->faker->unique()->safeEmail(),
            'first_name' => $this->faker->firstName(),
            'last_name'  => $this->faker->lastName(),
            'phone'      => null,
            'status'     => 'active',
        ];
    }
}
