<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\LoginLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoginLogFactory extends Factory
{
    protected $model = LoginLog::class;

    public function definition(): array
    {
        return [
            'user_id' => Customer::factory(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'type' => $this->faker->numberBetween(1, 3),
            'status' => $this->faker->numberBetween(0, 1),
            'fail_reason' => $this->faker->optional()->word(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->optional()->userAgent(),
            'device_id' => $this->faker->optional()->uuid(),
            'location' => $this->faker->optional()->city(),
            'created_at' => $this->faker->dateTime(),
        ];
    }
}
