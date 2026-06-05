<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Admin\Models;

use App\Domains\Admin\Models\AdminRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminRoleFactory extends Factory
{
    protected $model = AdminRole::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->jobTitle(),
            'code' => $this->faker->unique()->word() . '_' . $this->faker->randomNumber(3),
            'description' => $this->faker->sentence(),
            'status' => 1,
        ];
    }
}
