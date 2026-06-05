<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\SubAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubAccountFactory extends Factory
{
    protected $model = SubAccount::class;

    public function definition(): array
    {
        return [
            'parent_id' => Customer::factory(),
            'username' => $this->faker->unique()->userName(),
            'password_hash' => bcrypt('password'),
            'link_person' => $this->faker->name(),
            'mobile_phone' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->email(),
            'role' => $this->faker->optional()->randomElement(['客服', '设计', '财务']),
            'sub_permission' => $this->faker->randomElement([0, 1, 2, 4, 5, 8, 16]),
            'permissions_json' => null,
            'status' => 1,
        ];
    }
}
