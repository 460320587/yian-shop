<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Payment\Models;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_no' => 'P' . now()->format('Ymd') . $this->faker->unique()->numerify('######'),
            'order_no' => null,
            'customer_id' => Customer::factory(),
            'gateway' => $this->faker->randomElement(['wechat', 'alipay', 'wallet']),
            'amount' => $this->faker->numberBetween(1000, 100000),
            'status' => PaymentStatus::Pending->value,
            'transaction_no' => null,
            'credential' => null,
            'paid_at' => null,
            'expire_at' => now()->addMinutes(5),
        ];
    }
}
