<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Admin\Models;

use App\Domains\Admin\Models\AdminPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminPermissionFactory extends Factory
{
    protected $model = AdminPermission::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->unique()->word() . '.' . $this->faker->word(),
            'group' => $this->faker->randomElement(['user', 'order', 'product', 'finance', 'system']),
            'type' => $this->faker->randomElement([1, 2, 3, 4]),
        ];
    }
}
