<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'sku'                => strtoupper(Str::random(8)),
            'slug'               => Str::slug($title) . '-' . Str::random(4),
            'title'              => ucwords($title),
            'description'        => $this->faker->sentence(),
            'image_url'          => null,
            'cost_price_minor'   => $this->faker->numberBetween(100, 5000),
            'retail_price_minor' => $this->faker->numberBetween(1000, 10000),
            'currency'           => 'USD',
            'inventory'          => $this->faker->numberBetween(0, 100),
            'status'             => 'active',
            'supplier_id'        => null,
        ];
    }
}
