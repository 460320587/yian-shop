<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Coupon\Models;

use App\Domains\Coupon\Models\Coupon;
use App\Domains\Coupon\Models\CustomerCoupon;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerCouponFactory extends Factory
{
    protected $model = CustomerCoupon::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'coupon_id' => Coupon::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('CC#######')),
            'status' => $this->faker->numberBetween(1, 3),
            'claimed_at' => now(),
            'used_at' => null,
            'expired_at' => now()->addDays(30),
        ];
    }
}
