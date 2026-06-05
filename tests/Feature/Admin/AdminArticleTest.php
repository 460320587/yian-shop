<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Content\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArticleTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_articles(): void
    {
        $this->authAdmin();
        Article::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/articles');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'slug', 'type', 'status', 'view_count'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_filter_articles_by_type(): void
    {
        $this->authAdmin();
        Article::factory()->create(['type' => 1]);
        Article::factory()->create(['type' => 2]);

        $response = $this->getJson('/api/v1/admin/articles?type=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('data.0.type'));
    }

    public function test_admin_can_create_article(): void
    {
        $this->authAdmin();

        $response = $this->postJson('/api/v1/admin/articles', [
            'title' => '测试新闻',
            'slug' => 'test-news',
            'type' => 1,
            'content' => '这是新闻内容',
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', '测试新闻');

        $this->assertDatabaseHas('articles', ['slug' => 'test-news']);
    }

    public function test_admin_can_update_article(): void
    {
        $this->authAdmin();
        $article = Article::factory()->create(['title' => '旧标题']);

        $response = $this->putJson("/api/v1/admin/articles/{$article->id}", [
            'title' => '新标题',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => '新标题']);
    }

    public function test_admin_can_delete_article(): void
    {
        $this->authAdmin();
        $article = Article::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/articles/{$article->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    public function test_admin_can_toggle_article_status(): void
    {
        $this->authAdmin();
        $article = Article::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/articles/{$article->id}/toggle-status");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 0);
    }

    public function test_unauthenticated_cannot_access_articles(): void
    {
        $this->getJson('/api/v1/admin/articles')->assertUnauthorized();
    }
}
