<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Admin\Models;

use App\Domains\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('Admin@123456'),
            'real_name' => $this->faker->name(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->email(),
            'role' => $this->faker->randomElement([
                'super_admin', 'operator', 'customer_service', 'factory_manager', 'finance',
            ]),
            'status' => 1,
            'last_login_at' => null,
            'last_login_ip' => null,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => 'admin',
            'role' => 'super_admin',
        ]);
    }
}
