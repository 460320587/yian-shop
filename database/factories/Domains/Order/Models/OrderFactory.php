<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Order\Models;

use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_no' => 'Y' . now()->format('Ymd') . $this->faker->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'status' => $this->faker->numberBetween(10, 110),
            'out_status_name' => $this->faker->randomElement(['待付款', '生产中', '待收货', '已完成']),
            'total_amount' => $this->faker->numberBetween(1000, 100000),
            'deposit_sum' => $this->faker->numberBetween(0, 50000),
            'discount_sum' => $this->faker->numberBetween(0, 5000),
            'express_company' => $this->faker->optional()->company(),
            'delivery_type' => $this->faker->numberBetween(1, 3),
            'source' => $this->faker->numberBetween(1, 4),
            'remark' => $this->faker->optional()->sentence(),
            'paid_at' => $this->faker->optional()->dateTime(),
            'submitted_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
