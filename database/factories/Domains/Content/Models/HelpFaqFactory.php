<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Content\Models;

use App\Domains\Content\Models\HelpFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

class HelpFaqFactory extends Factory
{
    protected $model = HelpFaq::class;

    public function definition(): array
    {
        return [
            'category_id' => $this->faker->numberBetween(1, 10),
            'question' => $this->faker->sentence() . '?',
            'answer' => $this->faker->paragraph(),
            'keywords' => $this->faker->optional()->words(3, true),
            'view_count' => $this->faker->numberBetween(0, 1000),
            'helpful_count' => $this->faker->numberBetween(0, 100),
            'not_helpful_count' => $this->faker->numberBetween(0, 20),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'status' => 1,
        ];
    }
}
