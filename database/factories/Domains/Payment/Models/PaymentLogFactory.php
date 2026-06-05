<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Payment\Models;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentLogFactory extends Factory
{
    protected $model = PaymentLog::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'payment_no' => 'PAY' . $this->faker->unique()->numerify('##########'),
            'event' => $this->faker->randomElement(['create', 'callback', 'query', 'close', 'refund']),
            'from_status' => $this->faker->optional()->numberBetween(0, 10),
            'to_status' => $this->faker->optional()->numberBetween(0, 10),
            'amount' => $this->faker->numberBetween(100, 100000),
            'gateway_response' => null,
        ];
    }
}
