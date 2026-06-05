<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Dropship\Models;

use App\Domains\Dropship\Models\PlatformShop;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlatformShopFactory extends Factory
{
    protected $model = PlatformShop::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'platform' => $this->faker->numberBetween(1, 5),
            'shop_name' => $this->faker->company() . '店',
            'shop_auth_status' => $this->faker->numberBetween(0, 3),
            'auth_token' => $this->faker->optional()->uuid(),
            'expire_time' => $this->faker->optional()->dateTimeBetween('+1 day', '+30 days'),
        ];
    }
}
