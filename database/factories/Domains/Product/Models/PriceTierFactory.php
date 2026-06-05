<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\PriceTier;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceTierFactory extends Factory
{
    protected $model = PriceTier::class;

    public function definition(): array
    {
        $minQty = $this->faker->numberBetween(1, 100);

        return [
            'product_id' => Product::factory(),
            'min_qty' => $minQty,
            'max_qty' => $minQty + $this->faker->numberBetween(10, 1000),
            'unit_price' => $this->faker->randomFloat(4, 0.1, 99.9999),
            'status' => 1,
        ];
    }
}
