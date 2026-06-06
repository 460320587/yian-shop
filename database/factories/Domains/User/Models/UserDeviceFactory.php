<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDeviceFactory extends Factory
{
    protected $model = UserDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => Customer::factory(),
            'device_id' => $this->faker->uuid(),
            'device_name' => $this->faker->optional()->words(3, true),
            'platform' => $this->faker->randomElement(['ios', 'android', 'web', 'wxh5']),
            'ip_address' => $this->faker->ipv4(),
            'last_active_at' => $this->faker->optional()->dateTime(),
            'is_current' => $this->faker->numberBetween(0, 1),
        ];
    }
}
