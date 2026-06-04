<?php

declare(strict_types=1);

namespace Database\Factories\Domains\AfterSale\Models;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class AfterSaleFactory extends Factory
{
    protected $model = AfterSale::class;

    public function definition(): array
    {
        return [
            'after_sale_no' => 'A' . $this->faker->unique()->numerify('########'),
            'order_no' => $this->faker->bothify('Y######'),
            'customer_id' => Customer::factory(),
            'type' => $this->faker->randomElement([1, 2, 3, 4, 5]),
            'status' => $this->faker->randomElement([1, 2, 3, 4, 5, 6]),
            'reason' => $this->faker->sentence(),
            'description' => $this->faker->optional()->paragraph(),
            'images' => null,
            'refund_amount' => $this->faker->numberBetween(1000, 100000),
            'approved_amount' => 0,
            'audit_remark' => null,
            'completed_at' => null,
        ];
    }
}
