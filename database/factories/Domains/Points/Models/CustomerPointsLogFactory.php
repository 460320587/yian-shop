<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Points\Models;

use App\Domains\Points\Models\CustomerPointsLog;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerPointsLogFactory extends Factory
{
    protected $model = CustomerPointsLog::class;

    public function definition(): array
    {
        $points = $this->faker->numberBetween(10, 500);
        $balanceBefore = $this->faker->numberBetween(0, 1000);

        return [
            'customer_id' => Customer::factory(),
            'type' => $this->faker->randomElement([1, 2, 3, 4, 5]),
            'points' => $points,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $points,
            'order_no' => $this->faker->optional()->bothify('Y######'),
            'remark' => $this->faker->optional()->sentence(),
            'expired_at' => $this->faker->optional()->date(),
        ];
    }
}
