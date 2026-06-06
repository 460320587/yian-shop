<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\InventoryLog;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryLogFactory extends Factory
{
    protected $model = InventoryLog::class;

    public function definition(): array
    {
        $qtyBefore = $this->faker->numberBetween(0, 500);
        $change = $this->faker->numberBetween(-100, 100);
        $qtyAfter = max(0, $qtyBefore + $change);

        return [
            'product_id' => Product::factory(),
            'order_no' => $this->faker->optional()->regexify('Y[0-9]{8}[0-9]{6}'),
            'type' => $this->faker->numberBetween(1, 5),
            'qty_before' => $qtyBefore,
            'qty_change' => $change,
            'qty_after' => $qtyAfter,
            'reason' => $this->faker->optional()->sentence(),
            'created_by' => null,
            'created_at' => $this->faker->dateTime(),
        ];
    }
}
