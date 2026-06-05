<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domains\Content\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_published_articles(): void
    {
        Article::factory()->create(['status' => 1, 'type' => 1]);
        Article::factory()->create(['status' => 0, 'type' => 1]);
        Article::factory()->create(['status' => 2, 'type' => 1]);

        $response = $this->getJson('/api/v1/articles?type=1');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_can_view_article_detail(): void
    {
        $article = Article::factory()->create(['status' => 1]);

        $response = $this->getJson("/api/v1/articles/{$article->slug}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', $article->title)
            ->assertJsonPath('data.content', $article->content);
    }

    public function test_view_article_increments_view_count(): void
    {
        $article = Article::factory()->create(['status' => 1, 'view_count' => 5]);

        $this->getJson("/api/v1/articles/{$article->slug}");

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'view_count' => 6,
        ]);
    }

    public function test_guest_cannot_view_unpublished_article(): void
    {
        $article = Article::factory()->create(['status' => 0]);

        $response = $this->getJson("/api/v1/articles/{$article->slug}");

        $response->assertNotFound();
    }
}
