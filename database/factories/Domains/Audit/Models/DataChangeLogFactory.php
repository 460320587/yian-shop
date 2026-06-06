<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Audit\Models;

use App\Domains\Audit\Models\DataChangeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataChangeLogFactory extends Factory
{
    protected $model = DataChangeLog::class;

    public function definition(): array
    {
        return [
            'table_name' => $this->faker->randomElement(['orders', 'customers', 'products']),
            'record_id' => $this->faker->numberBetween(1, 1000),
            'action_type' => $this->faker->randomElement([1, 2, 3]),
            'field_name' => $this->faker->randomElement(['status', 'price', 'name']),
            'old_value' => $this->faker->optional()->word(),
            'new_value' => $this->faker->word(),
            'operator_id' => $this->faker->optional()->numberBetween(1, 100),
            'operator_name' => $this->faker->optional()->name(),
            'operator_type' => $this->faker->randomElement([1, 2]),
            'request_id' => $this->faker->optional()->uuid(),
            'ip_address' => $this->faker->optional()->ipv4(),
        ];
    }
}
