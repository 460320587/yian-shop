<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Notification\Models;

use App\Domains\Notification\Models\CustomerNotification;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerNotificationFactory extends Factory
{
    protected $model = CustomerNotification::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'type' => $this->faker->randomElement(['order', 'payment', 'system', 'promotion']),
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'is_read' => 0,
            'action_url' => $this->faker->optional()->url(),
            'action_text' => $this->faker->optional()->word(),
        ];
    }
}
