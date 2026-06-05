<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Admin\Models\AdminPermission;
use App\Domains\Admin\Models\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_roles(): void
    {
        $this->authAdmin();
        AdminRole::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/roles');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'code', 'description', 'status'],
                ],
            ]);
    }

    public function test_admin_can_create_role(): void
    {
        $this->authAdmin();

        $response = $this->postJson('/api/v1/admin/roles', [
            'name' => '运营专员',
            'code' => 'operator',
            'description' => '负责日常运营',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.name', '运营专员');

        $this->assertDatabaseHas('admin_roles', ['code' => 'operator']);
    }

    public function test_admin_can_update_role(): void
    {
        $this->authAdmin();
        $role = AdminRole::factory()->create(['name' => '旧名称']);

        $response = $this->putJson("/api/v1/admin/roles/{$role->id}", [
            'name' => '新名称',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('admin_roles', ['id' => $role->id, 'name' => '新名称']);
    }

    public function test_admin_can_delete_role(): void
    {
        $this->authAdmin();
        $role = AdminRole::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/roles/{$role->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSoftDeleted('admin_roles', ['id' => $role->id]);
    }

    public function test_admin_can_toggle_role_status(): void
    {
        $this->authAdmin();
        $role = AdminRole::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/roles/{$role->id}/toggle-status");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 0);
    }

    public function test_admin_can_assign_permissions_to_role(): void
    {
        $this->authAdmin();
        $role = AdminRole::factory()->create();
        $perm1 = AdminPermission::factory()->create();
        $perm2 = AdminPermission::factory()->create();

        $response = $this->putJson("/api/v1/admin/roles/{$role->id}/permissions", [
            'permission_ids' => [$perm1->id, $perm2->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('admin_role_permission', [
            'role_id' => $role->id,
            'permission_id' => $perm1->id,
        ]);
        $this->assertDatabaseHas('admin_role_permission', [
            'role_id' => $role->id,
            'permission_id' => $perm2->id,
        ]);
    }

    public function test_admin_can_get_role_permissions(): void
    {
        $this->authAdmin();
        $role = AdminRole::factory()->create();
        $perm = AdminPermission::factory()->create();
        $role->permissions()->attach($perm->id);

        $response = $this->getJson("/api/v1/admin/roles/{$role->id}/permissions");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_cannot_access_roles(): void
    {
        $this->getJson('/api/v1/admin/roles')->assertUnauthorized();
    }
}
