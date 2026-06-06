<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => Customer::factory(),
            'real_name' => $this->faker->optional()->name(),
            'gender' => $this->faker->optional()->numberBetween(0, 2),
            'birthday' => $this->faker->optional()->date(),
            'id_card' => $this->faker->optional()->regexify('[0-9]{17}[0-9X]'),
            'industry' => $this->faker->optional()->word(),
            'position' => $this->faker->optional()->jobTitle(),
        ];
    }
}
