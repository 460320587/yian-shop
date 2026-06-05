<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Content\Models;

use App\Domains\Content\Models\KnowledgeArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_article(): void
    {
        $article = KnowledgeArticle::factory()->create();
        $this->assertDatabaseHas('knowledge_articles', ['id' => $article->id]);
    }

    public function test_tags_is_cast_to_array(): void
    {
        $article = KnowledgeArticle::factory()->create(['tags' => ['新手', '教程']]);
        $this->assertIsArray($article->tags);
        $this->assertContains('新手', $article->tags);
    }

    public function test_casts_are_correct(): void
    {
        $article = new KnowledgeArticle();
        $casts = $article->getCasts();

        $this->assertArrayHasKey('view_count', $casts);
        $this->assertArrayHasKey('like_count', $casts);
        $this->assertArrayHasKey('publish_status', $casts);
    }
}
