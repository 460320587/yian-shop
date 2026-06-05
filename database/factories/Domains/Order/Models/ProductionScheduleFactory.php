<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Order\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\ProductionSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductionScheduleFactory extends Factory
{
    protected $model = ProductionSchedule::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'factory_id' => null,
            'schedule_date' => $this->faker->date(),
            'start_time' => $this->faker->optional()->time(),
            'end_time' => $this->faker->optional()->time(),
            'process_name' => $this->faker->randomElement(['印刷', '裁切', '覆膜', '装订']),
            'equipment_id' => null,
            'operator_id' => null,
            'status' => $this->faker->numberBetween(0, 4),
            'priority' => $this->faker->numberBetween(1, 5),
            'estimated_hours' => $this->faker->optional()->randomFloat(2, 1, 24),
            'actual_hours' => null,
            'progress' => $this->faker->numberBetween(0, 100),
            'delay_reason' => null,
        ];
    }
}
