<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Content\Models;

use App\Domains\Content\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'slug' => $this->faker->unique()->slug(),
            'type' => $this->faker->randomElement([1, 2, 3, 4]),
            'content' => $this->faker->paragraphs(3, true),
            'summary' => $this->faker->sentence(10),
            'cover' => $this->faker->optional()->imageUrl(),
            'author' => $this->faker->name(),
            'view_count' => $this->faker->numberBetween(0, 1000),
            'sort' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement([0, 1, 2]),
            'published_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
