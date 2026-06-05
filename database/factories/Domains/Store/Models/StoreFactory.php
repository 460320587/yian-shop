<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Store\Models;

use App\Domains\Store\Models\Store;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'store_code' => $this->faker->unique()->bothify('ST-####'),
            'store_name' => $this->faker->company() . '印刷店',
            'store_type' => $this->faker->numberBetween(1, 3),
            'province' => $this->faker->optional()->state(),
            'city' => $this->faker->optional()->city(),
            'district' => $this->faker->optional()->streetName(),
            'address' => $this->faker->optional()->address(),
            'longitude' => $this->faker->optional()->longitude(),
            'latitude' => $this->faker->optional()->latitude(),
            'contact_phone' => $this->faker->optional()->phoneNumber(),
            'manager_id' => Customer::factory(),
            'manager_name' => $this->faker->name(),
            'coverage_area' => $this->faker->optional()->city(),
            'business_hours' => '09:00-18:00',
            'capacity_daily' => $this->faker->numberBetween(50, 500),
            'current_load' => $this->faker->numberBetween(0, 100),
            'status' => 1,
            'support_pickup' => true,
            'support_delivery' => $this->faker->boolean(),
            'delivery_range' => $this->faker->numberBetween(0, 10000),
            'factory_id' => null,
        ];
    }
}
