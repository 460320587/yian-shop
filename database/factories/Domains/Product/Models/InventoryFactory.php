<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'available_qty' => $this->faker->numberBetween(0, 1000),
            'reserved_qty' => $this->faker->numberBetween(0, 100),
            'locked_qty' => $this->faker->numberBetween(0, 50),
            'safety_stock' => $this->faker->numberBetween(10, 100),
            'version' => $this->faker->numberBetween(0, 10),
        ];
    }
}
