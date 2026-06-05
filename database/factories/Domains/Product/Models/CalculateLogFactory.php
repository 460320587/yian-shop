<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Product\Models\CalculateLog;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalculateLogFactory extends Factory
{
    protected $model = CalculateLog::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'params' => ['quantity' => $this->faker->numberBetween(1, 1000), 'size' => 'A4'],
            'formula' => 'quantity * unit_price',
            'result' => $this->faker->numberBetween(1000, 100000),
            'calculated_at' => now(),
        ];
    }
}
