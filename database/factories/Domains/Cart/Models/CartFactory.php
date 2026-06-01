<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Cart\Models;

use App\Domains\Cart\Models\Cart;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'total_count' => 0,
            'selected_subtotal' => 0,
        ];
    }
}
