<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Logistics\Models;

use App\Domains\Logistics\Models\ExpressTrack;
use App\Domains\Logistics\Models\OrderDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpressTrackFactory extends Factory
{
    protected $model = ExpressTrack::class;

    public function definition(): array
    {
        return [
            'delivery_id' => OrderDelivery::factory(),
            'track_time' => $this->faker->dateTime(),
            'location' => $this->faker->city(),
            'description' => $this->faker->randomElement([
                '快件已揽收',
                '快件到达【' . $this->faker->city() . '转运中心】',
                '快件已从【' . $this->faker->city() . '转运中心】发出',
                '快件已签收',
            ]),
        ];
    }
}
