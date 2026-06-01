<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Product\Models;

use App\Domains\Product\Models\CustomerFavorite;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFavoriteFactory extends Factory
{
    protected $model = CustomerFavorite::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'product_id' => Product::factory(),
            'remark' => $this->faker->optional()->sentence(),
            'status' => 1,
        ];
    }
}
