<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Audit\Models;

use App\Domains\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'admin_id' => $this->faker->numberBetween(1, 10),
            'admin_name' => $this->faker->name(),
            'action' => $this->faker->randomElement(['login', 'logout', 'create', 'update', 'delete']),
            'model_type' => $this->faker->randomElement(['Product', 'Order', 'Customer']),
            'model_id' => $this->faker->numberBetween(1, 100),
            'before_data' => null,
            'after_data' => null,
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'result' => $this->faker->randomElement([0, 1]),
            'remark' => $this->faker->optional()->sentence(),
        ];
    }
}
