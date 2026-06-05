<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Payment\Models;

use App\Domains\Payment\Models\WalletTransaction;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'type' => $this->faker->numberBetween(1, 6),
            'amount' => $this->faker->numberBetween(-10000, 10000),
            'balance_before' => $this->faker->numberBetween(0, 50000),
            'balance_after' => $this->faker->numberBetween(0, 50000),
            'order_no' => $this->faker->optional()->regexify('Y[0-9]{14}'),
            'payment_no' => $this->faker->optional()->regexify('P[0-9]{14}'),
            'remark' => $this->faker->optional()->sentence(),
            'status' => $this->faker->numberBetween(1, 3),
        ];
    }
}
