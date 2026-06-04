<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Logistics\Models;

use App\Domains\Logistics\Models\OrderDelivery;
use App\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderDeliveryFactory extends Factory
{
    protected $model = OrderDelivery::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'carrier_name' => $this->faker->randomElement(['顺丰速运', '中通快递', '圆通速递', '韵达快递']),
            'tracking_no' => $this->faker->unique()->numerify('SF###########'),
            'status' => $this->faker->randomElement([1, 2, 3]),
            'shipped_at' => $this->faker->optional()->dateTime(),
            'delivered_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
