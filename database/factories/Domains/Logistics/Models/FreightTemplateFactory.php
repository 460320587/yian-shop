<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Logistics\Models;

use App\Domains\Logistics\Models\Carrier;
use App\Domains\Logistics\Models\FreightTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class FreightTemplateFactory extends Factory
{
    protected $model = FreightTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['标准快递', '次日达', '经济快递']),
            'carrier_id' => Carrier::factory(),
            'calculation_type' => $this->faker->numberBetween(1, 3),
            'first_weight' => 1.0,
            'first_price' => $this->faker->randomFloat(2, 5, 20),
            'continue_weight' => 1.0,
            'continue_price' => $this->faker->randomFloat(2, 2, 10),
            'free_threshold' => $this->faker->optional()->randomFloat(2, 50, 200),
            'regions' => [['province' => '广东', 'city' => '深圳', 'surcharge' => 0.00]],
            'status' => 1,
        ];
    }
}
