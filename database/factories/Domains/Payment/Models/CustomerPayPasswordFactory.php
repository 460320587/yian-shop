<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Payment\Models;

use App\Domains\Payment\Models\CustomerPayPassword;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CustomerPayPasswordFactory extends Factory
{
    protected $model = CustomerPayPassword::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'pay_password_hash' => Hash::make('123456'),
            'fail_count' => 0,
            'locked_until' => null,
        ];
    }
}
