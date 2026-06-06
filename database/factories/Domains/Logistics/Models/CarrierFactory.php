<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Logistics\Models;

use App\Domains\Logistics\Models\Carrier;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarrierFactory extends Factory
{
    protected $model = Carrier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['顺丰速运', '中通快递', '圆通速递', '韵达快递', 'EMS']),
            'code' => $this->faker->unique()->randomElement(['sf', 'zto', 'yto', 'yd', 'ems']),
            'api_type' => $this->faker->randomElement(['kdniao', 'kuaidi100', 'official']),
            'config' => ['app_id' => $this->faker->uuid(), 'app_key' => $this->faker->uuid()],
            'is_default' => 0,
            'status' => 1,
        ];
    }
}
