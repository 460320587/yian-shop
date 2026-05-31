<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Enterprise\Models;

use App\Domains\Enterprise\Models\CustomerBrand;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerBrandFactory extends Factory
{
    protected $model = CustomerBrand::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => $this->faker->company(),
            'type' => $this->faker->numberBetween(0, 3),
            'status' => $this->faker->numberBetween(0, 2),
            'entruster' => $this->faker->optional()->company(),
            'valid_type' => $this->faker->numberBetween(0, 1),
            'valid_start' => $this->faker->optional()->date(),
            'valid_end' => $this->faker->optional()->date(),
            'attachment' => $this->faker->optional()->url(),
            'reject_reason' => null,
        ];
    }
}
