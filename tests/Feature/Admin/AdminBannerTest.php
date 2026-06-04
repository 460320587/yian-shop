<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Portal\Models\Banner;
use App\Domains\Portal\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBannerTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    // ========== Banner ==========

    public function test_admin_can_list_banners(): void
    {
        $this->authAdmin();
        Banner::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/banners');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'image', 'link_type', 'link_target', 'position', 'sort', 'status'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_create_banner(): void
    {
        $this->authAdmin();

        $response = $this->postJson('/api/v1/admin/banners', [
            'title' => '首页Banner',
            'image' => 'https://example.com/banner.jpg',
            'link_type' => 'product',
            'link_target' => '42',
            'position' => 'home',
            'sort' => 10,
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', '首页Banner');

        $this->assertDatabaseHas('banners', [
            'title' => '首页Banner',
            'image' => 'https://example.com/banner.jpg',
            'link_type' => 'product',
            'position' => 'home',
            'status' => 1,
        ]);
    }

    public function test_admin_can_update_banner(): void
    {
        $this->authAdmin();
        $banner = Banner::factory()->create();

        $response = $this->putJson("/api/v1/admin/banners/{$banner->id}", [
            'title' => '更新后的标题',
            'sort' => 99,
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', '更新后的标题')
            ->assertJsonPath('data.sort', 99);

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'title' => '更新后的标题',
            'sort' => 99,
        ]);
    }

    public function test_admin_can_delete_banner(): void
    {
        $this->authAdmin();
        $banner = Banner::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/banners/{$banner->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSoftDeleted('banners', ['id' => $banner->id]);
    }

    public function test_admin_cannot_delete_nonexistent_banner(): void
    {
        $this->authAdmin();

        $response = $this->deleteJson('/api/v1/admin/banners/99999');

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }

    // ========== Announcement ==========

    public function test_admin_can_list_announcements(): void
    {
        $this->authAdmin();
        Announcement::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/announcements');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'content', 'type', 'is_popup', 'status'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_create_announcement(): void
    {
        $this->authAdmin();

        $response = $this->postJson('/api/v1/admin/announcements', [
            'title' => '重要通知',
            'content' => '系统将于今晚维护',
            'type' => 'general',
            'is_popup' => 1,
            'status' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', '重要通知');

        $this->assertDatabaseHas('announcements', [
            'title' => '重要通知',
            'content' => '系统将于今晚维护',
            'type' => 'general',
            'is_popup' => 1,
        ]);
    }

    public function test_admin_can_update_announcement(): void
    {
        $this->authAdmin();
        $announcement = Announcement::factory()->create();

        $response = $this->putJson("/api/v1/admin/announcements/{$announcement->id}", [
            'title' => '更新后的公告',
            'status' => 0,
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.title', '更新后的公告')
            ->assertJsonPath('data.status', 0);

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => '更新后的公告',
            'status' => 0,
        ]);
    }

    public function test_admin_can_delete_announcement(): void
    {
        $this->authAdmin();
        $announcement = Announcement::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/announcements/{$announcement->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSoftDeleted('announcements', ['id' => $announcement->id]);
    }

    public function test_admin_cannot_delete_nonexistent_announcement(): void
    {
        $this->authAdmin();

        $response = $this->deleteJson('/api/v1/admin/announcements/99999');

        $response->assertNotFound()
            ->assertJsonPath('code', 404);
    }

    public function test_unauthenticated_cannot_access_banners(): void
    {
        $this->getJson('/api/v1/admin/banners')
            ->assertUnauthorized();
    }
}
