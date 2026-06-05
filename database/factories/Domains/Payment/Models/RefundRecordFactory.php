<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Payment\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefundRecordFactory extends Factory
{
    protected $model = RefundRecord::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'customer_id' => Customer::factory(),
            'refund_no' => 'R' . now()->format('Ymd') . $this->faker->unique()->numerify('######'),
            'amount' => $this->faker->numberBetween(1000, 10000),
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->numberBetween(0, 4),
            'approved_by' => null,
            'approved_at' => null,
            'refund_path' => $this->faker->randomElement(['original', 'wallet', 'bank_card']),
            'gateway_refund_no' => null,
            'completed_at' => null,
        ];
    }
}
