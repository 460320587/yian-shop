<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Audit\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_audit_logs(): void
    {
        $this->authAdmin();
        AuditLog::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/admin/audit-logs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'admin_id', 'admin_name', 'action', 'model_type', 'model_id', 'ip', 'created_at'],
                    ],
                ],
            ]);
        $this->assertCount(5, $response->json('data.data'));
    }

    public function test_admin_can_filter_audit_logs_by_action(): void
    {
        $this->authAdmin();
        AuditLog::factory()->create(['action' => 'login']);
        AuditLog::factory()->create(['action' => 'update']);

        $response = $this->getJson('/api/v1/admin/audit-logs?action=login');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('login', $response->json('data.data.0.action'));
    }

    public function test_admin_can_view_audit_log_detail(): void
    {
        $this->authAdmin();
        $log = AuditLog::factory()->create([
            'action' => 'update',
            'before_data' => ['name' => '旧名称'],
            'after_data' => ['name' => '新名称'],
        ]);

        $response = $this->getJson("/api/v1/admin/audit-logs/{$log->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.action', 'update')
            ->assertJsonPath('data.before_data.name', '旧名称')
            ->assertJsonPath('data.after_data.name', '新名称');
    }

    public function test_unauthenticated_cannot_access_audit_logs(): void
    {
        $this->getJson('/api/v1/admin/audit-logs')
            ->assertUnauthorized();
    }
}
