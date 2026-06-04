<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Ticket\Models;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'order_id' => null,
            'ticket_no' => 'TK' . now()->format('Ymd') . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'type' => $this->faker->randomElement([1, 2, 3, 4, 5]),
            'status' => $this->faker->randomElement([1, 2, 3, 4, 5]),
            'priority' => $this->faker->randomElement([1, 2, 3, 4]),
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'images' => null,
            'expected_resolution' => $this->faker->optional()->sentence(),
            'satisfaction' => null,
            'remark' => null,
            'processed_by' => null,
            'processed_at' => null,
            'completed_at' => null,
        ];
    }
}
