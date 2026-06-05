<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Order\Models;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderStatusLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderStatusLogFactory extends Factory
{
    protected $model = OrderStatusLog::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'from_status' => $this->faker->numberBetween(0, 50),
            'to_status' => $this->faker->numberBetween(0, 50),
            'remark' => $this->faker->optional()->sentence(),
            'operator_id' => Admin::factory(),
            'operator_type' => $this->faker->randomElement(['admin', 'system', 'customer']),
        ];
    }
}
