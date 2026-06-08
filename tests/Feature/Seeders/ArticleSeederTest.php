<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\Content\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_seeder_creates_articles(): void
    {
        $this->seed(\Database\Seeders\ArticleSeeder::class);

        $this->assertDatabaseCount('articles', 6);
        $this->assertDatabaseHas('articles', ['slug' => 'company-intro', 'type' => 1]);
        $this->assertDatabaseHas('articles', ['slug' => 'spring-holiday', 'type' => 2]);
        $this->assertDatabaseHas('articles', ['slug' => 'how-to-order', 'type' => 3]);
    }

    public function test_article_seeder_has_published_articles(): void
    {
        $this->seed(\Database\Seeders\ArticleSeeder::class);

        $published = Article::where('status', 1)->count();
        $this->assertGreaterThanOrEqual(3, $published);
    }
}
