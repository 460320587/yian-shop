<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Notification\Models;

use App\Domains\Notification\Models\NotificationLog;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'template_code' => $this->faker->optional()->bothify('TPL-####'),
            'channel' => $this->faker->randomElement([1, 2, 4, 5]),
            'type' => $this->faker->randomElement(['order', 'system', 'promo']),
            'recipient' => $this->faker->phoneNumber(),
            'title' => $this->faker->optional()->sentence(),
            'content' => $this->faker->paragraph(),
            'variables' => null,
            'status' => $this->faker->randomElement([0, 1, 2, 3]),
            'sent_at' => $this->faker->optional()->dateTime(),
            'read_at' => $this->faker->optional()->dateTime(),
            'response' => null,
            'error_msg' => null,
            'retry_count' => $this->faker->numberBetween(0, 3),
            'dedup_key' => null,
            'failover_from' => null,
            'aggregated_id' => null,
            'biz_id' => null,
            'biz_type' => null,
        ];
    }
}
