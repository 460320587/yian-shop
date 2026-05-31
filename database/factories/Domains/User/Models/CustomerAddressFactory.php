<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'province_name' => $this->faker->state(),
            'city_name' => $this->faker->city(),
            'county_name' => $this->faker->city(),
            'detail_address' => $this->faker->streetAddress(),
            'contact_name' => $this->faker->name(),
            'contact_phone' => $this->faker->phoneNumber(),
            'is_default' => false,
        ];
    }
}
