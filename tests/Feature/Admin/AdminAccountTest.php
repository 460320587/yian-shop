<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Admin\Models\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_admins(): void
    {
        $this->authAdmin();
        Admin::factory()->count(2)->create();

        $response = $this->getJson('/api/v1/admin/admins');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'username', 'real_name', 'phone', 'email', 'status'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_create_admin(): void
    {
        $this->authAdmin();
        $role = AdminRole::factory()->create();

        $response = $this->postJson('/api/v1/admin/admins', [
            'username' => 'newadmin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'real_name' => '新员工',
            'phone' => '13800138001',
            'email' => 'new@example.com',
            'role_id' => $role->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('admins', ['username' => 'newadmin', 'real_name' => '新员工']);
    }

    public function test_admin_can_update_admin(): void
    {
        $this->authAdmin();
        $target = Admin::factory()->create(['real_name' => '旧名字']);

        $response = $this->putJson("/api/v1/admin/admins/{$target->id}", [
            'real_name' => '新名字',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('admins', ['id' => $target->id, 'real_name' => '新名字']);
    }

    public function test_admin_can_toggle_admin_status(): void
    {
        $this->authAdmin();
        $target = Admin::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/admins/{$target->id}/toggle-status");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 0);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->authAdmin();

        $response = $this->deleteJson("/api/v1/admin/admins/{$admin->id}");

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_admin_can_reset_password(): void
    {
        $this->authAdmin();
        $target = Admin::factory()->create();

        $response = $this->putJson("/api/v1/admin/admins/{$target->id}/reset-password", [
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);
    }

    public function test_unauthenticated_cannot_access_admins(): void
    {
        $this->getJson('/api/v1/admin/admins')->assertUnauthorized();
    }
}
