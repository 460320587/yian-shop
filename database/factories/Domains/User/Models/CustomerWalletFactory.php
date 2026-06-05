<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerWalletFactory extends Factory
{
    protected $model = CustomerWallet::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'balance' => $this->faker->numberBetween(0, 100000),
            'frozen_amount' => $this->faker->numberBetween(0, 10000),
            'total_recharge' => $this->faker->numberBetween(0, 200000),
            'total_consume' => $this->faker->numberBetween(0, 150000),
            'status' => 1,
            'version' => 0,
        ];
    }
}
