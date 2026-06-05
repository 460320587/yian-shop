<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Content\Models;

use App\Domains\Content\Models\KnowledgeArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

class KnowledgeArticleFactory extends Factory
{
    protected $model = KnowledgeArticle::class;

    public function definition(): array
    {
        return [
            'category_id' => $this->faker->numberBetween(1, 10),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'summary' => $this->faker->optional()->sentence(),
            'author' => $this->faker->name(),
            'tags' => $this->faker->words(3),
            'cover_image' => $this->faker->optional()->imageUrl(),
            'view_count' => $this->faker->numberBetween(0, 5000),
            'like_count' => $this->faker->numberBetween(0, 200),
            'publish_status' => $this->faker->numberBetween(0, 1),
            'published_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
