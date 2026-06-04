<?php

declare(strict_types=1);

namespace Database\Factories\Domains\System\Models;

use App\Domains\System\Models\SystemConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemConfigFactory extends Factory
{
    protected $model = SystemConfig::class;

    public function definition(): array
    {
        return [
            'config_key' => $this->faker->unique()->word(),
            'config_value' => $this->faker->word(),
            'type' => $this->faker->randomElement(['string', 'int', 'bool', 'json']),
            'description' => $this->faker->sentence(),
            'group' => $this->faker->randomElement(['basic', 'system', 'payment', 'seo']),
        ];
    }
}
