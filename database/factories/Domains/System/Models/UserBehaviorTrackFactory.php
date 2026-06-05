<?php

declare(strict_types=1);

namespace Database\Factories\Domains\System\Models;

use App\Domains\System\Models\UserBehaviorTrack;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserBehaviorTrackFactory extends Factory
{
    protected $model = UserBehaviorTrack::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'session_id' => $this->faker->uuid(),
            'event_type' => $this->faker->randomElement(['page_click', 'scroll', 'form_submit', 'page_view']),
            'page_path' => $this->faker->randomElement(['/home', '/product/1', '/cart', '/order']),
            'element_id' => $this->faker->optional()->word(),
            'element_text' => $this->faker->optional()->words(3, true),
            'referrer' => $this->faker->optional()->url(),
            'device_type' => $this->faker->randomElement(['pc', 'mobile', 'tablet']),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'os' => $this->faker->randomElement(['Windows', 'macOS', 'iOS', 'Android']),
            'event_data' => ['x' => $this->faker->numberBetween(0, 1920), 'y' => $this->faker->numberBetween(0, 1080)],
        ];
    }
}
