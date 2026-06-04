<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Coupon\Models;

use App\Domains\Coupon\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('COUPON####')),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->numberBetween(1, 3),
            'value' => $this->faker->numberBetween(100, 5000),
            'min_amount' => $this->faker->numberBetween(0, 10000),
            'max_discount' => $this->faker->numberBetween(0, 5000),
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(30),
            'total_count' => $this->faker->numberBetween(10, 1000),
            'per_customer_limit' => $this->faker->numberBetween(1, 5),
            'claimed_count' => 0,
            'used_count' => 0,
            'status' => 1,
        ];
    }
}
