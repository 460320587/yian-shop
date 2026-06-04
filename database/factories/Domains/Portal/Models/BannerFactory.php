<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Portal\Models;

use App\Domains\Portal\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'image' => $this->faker->imageUrl(1920, 600),
            'image_mobile' => $this->faker->imageUrl(750, 400),
            'link_type' => $this->faker->randomElement(['product', 'category', 'url']),
            'link_target' => $this->faker->url(),
            'position' => 'home',
            'sort' => $this->faker->numberBetween(0, 100),
            'display_start' => null,
            'display_end' => null,
            'status' => 1,
        ];
    }
}
