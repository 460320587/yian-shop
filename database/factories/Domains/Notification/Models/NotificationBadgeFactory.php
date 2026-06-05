<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Notification\Models;

use App\Domains\Notification\Models\NotificationBadge;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationBadgeFactory extends Factory
{
    protected $model = NotificationBadge::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'notification_type' => $this->faker->randomElement(['order', 'aftersale', 'system', 'message']),
            'unread_count' => $this->faker->numberBetween(0, 99),
            'last_read_time' => $this->faker->optional()->dateTime(),
        ];
    }
}
