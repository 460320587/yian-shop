<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Sample\Models;

use App\Domains\Product\Models\Product;
use App\Domains\Sample\Models\SampleOrder;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class SampleOrderFactory extends Factory
{
    protected $model = SampleOrder::class;

    public function definition(): array
    {
        $unitPrice = $this->faker->numberBetween(1000, 10000);
        $quantity = $this->faker->numberBetween(1, 10);
        $discount = $this->faker->numberBetween(0, 500);
        $total = $unitPrice * $quantity - $discount;

        return [
            'customer_id' => Customer::factory(),
            'order_no' => 'S' . $this->faker->unique()->numerify('########'),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discount,
            'total_amount' => max(0, $total),
            'status' => $this->faker->randomElement([100, 101, 102, 103, 104, 105]),
            'address_snapshot' => null,
            'remark' => $this->faker->optional()->sentence(),
            'paid_at' => null,
            'shipped_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }
}
