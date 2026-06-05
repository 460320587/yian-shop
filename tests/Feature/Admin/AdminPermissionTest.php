<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Admin\Models\AdminPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_permissions(): void
    {
        $this->authAdmin();
        AdminPermission::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/permissions');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'code', 'group', 'type'],
                ],
            ]);
    }

    public function test_admin_can_filter_permissions_by_group(): void
    {
        $this->authAdmin();
        AdminPermission::factory()->create(['group' => 'order']);
        AdminPermission::factory()->create(['group' => 'product']);

        $response = $this->getJson('/api/v1/admin/permissions?group=order');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('order', $response->json('data.0.group'));
    }

    public function test_unauthenticated_cannot_access_permissions(): void
    {
        $this->getJson('/api/v1/admin/permissions')->assertUnauthorized();
    }
}
