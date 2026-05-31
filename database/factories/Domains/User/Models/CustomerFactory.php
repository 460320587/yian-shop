<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User\Models;

use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'phone' => $this->faker->unique()->phoneNumber(),
            'password' => bcrypt('password'),
            'nickname' => $this->faker->name(),
            'avatar' => $this->faker->optional()->imageUrl(),
            'type' => $this->faker->numberBetween(3, 8),
            'auth_status' => $this->faker->numberBetween(0, 3),
            'vip_level' => $this->faker->numberBetween(0, 8),
            'grow_value' => $this->faker->numberBetween(0, 10000),
            'balance' => $this->faker->numberBetween(0, 100000),
            'status' => 1,
            'link_person' => $this->faker->optional()->name(),
            'qq' => $this->faker->optional()->numerify('#########'),
            'register_ip' => $this->faker->ipv4(),
            'last_login_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
