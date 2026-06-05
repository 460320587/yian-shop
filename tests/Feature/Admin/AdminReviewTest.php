<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Product\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_reviews(): void
    {
        $this->authAdmin();
        ProductReview::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/reviews');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'customer_id', 'product_id', 'order_id', 'rating', 'content', 'is_show'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_filter_reviews_by_product_id(): void
    {
        $this->authAdmin();
        $review1 = ProductReview::factory()->create();
        ProductReview::factory()->create();

        $response = $this->getJson("/api/v1/admin/reviews?product_id={$review1->product_id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($review1->product_id, $response->json('data.0.product_id'));
    }

    public function test_admin_can_view_review_detail(): void
    {
        $this->authAdmin();
        $review = ProductReview::factory()->create();

        $response = $this->getJson("/api/v1/admin/reviews/{$review->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.content', $review->content)
            ->assertJsonStructure([
                'data' => ['id', 'customer_id', 'product_id', 'order_id', 'rating', 'content', 'images', 'reply', 'reply_at', 'is_show', 'created_at'],
            ]);
    }

    public function test_admin_can_reply_review(): void
    {
        $this->authAdmin();
        $review = ProductReview::factory()->create(['reply' => null, 'reply_at' => null]);

        $response = $this->putJson("/api/v1/admin/reviews/{$review->id}/reply", [
            'reply' => '感谢您的评价，我们会继续努力！',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.reply', '感谢您的评价，我们会继续努力！');

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'reply' => '感谢您的评价，我们会继续努力！',
        ]);
    }

    public function test_admin_reply_requires_content(): void
    {
        $this->authAdmin();
        $review = ProductReview::factory()->create();

        $response = $this->putJson("/api/v1/admin/reviews/{$review->id}/reply", [
            'reply' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_admin_can_toggle_review_show(): void
    {
        $this->authAdmin();
        $review = ProductReview::factory()->create(['is_show' => true]);

        $response = $this->putJson("/api/v1/admin/reviews/{$review->id}/toggle-show");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.is_show', false);

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'is_show' => false,
        ]);
    }

    public function test_admin_can_delete_review(): void
    {
        $this->authAdmin();
        $review = ProductReview::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/reviews/{$review->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSoftDeleted('product_reviews', ['id' => $review->id]);
    }

    public function test_unauthenticated_cannot_access_reviews(): void
    {
        $this->getJson('/api/v1/admin/reviews')->assertUnauthorized();
    }
}
