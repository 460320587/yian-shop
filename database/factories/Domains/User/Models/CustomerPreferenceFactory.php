<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User\Models;

use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerPreferenceFactory extends Factory
{
    protected $model = CustomerPreference::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'product_layout_type' => $this->faker->numberBetween(1, 2),
            'category_grid_type' => $this->faker->numberBetween(1, 3),
            'user_center_menu_fold' => $this->faker->numberBetween(0, 1),
            'pay_now' => $this->faker->numberBetween(0, 1),
        ];
    }
}
