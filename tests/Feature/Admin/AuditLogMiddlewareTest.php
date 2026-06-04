<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\System\Models\SystemConfig;
use App\Domains\Portal\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1, 'username' => 'test_admin', 'real_name' => '测试管理员']);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_post_request_creates_audit_log(): void
    {
        $this->authAdmin();
        $config = SystemConfig::factory()->create();

        $this->putJson('/api/v1/admin/system-configs/batch', [
            'configs' => [['id' => $config->id, 'config_value' => 'test']],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'admin_name' => '测试管理员',
            'action' => 'update',
            'model_type' => 'SystemConfig',
            'result' => 1,
        ]);
    }

    public function test_admin_put_request_creates_audit_log(): void
    {
        $this->authAdmin();
        $config = SystemConfig::factory()->create();

        $this->putJson('/api/v1/admin/system-configs/' . $config->id, [
            'config_value' => 'updated',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'model_type' => 'SystemConfig',
            'result' => 1,
        ]);
    }

    public function test_admin_delete_request_creates_audit_log(): void
    {
        $this->authAdmin();
        $banner = Banner::factory()->create();

        $this->deleteJson('/api/v1/admin/banners/' . $banner->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'delete',
            'model_type' => 'Banner',
            'result' => 1,
        ]);
    }

    public function test_admin_get_request_does_not_create_audit_log(): void
    {
        $this->authAdmin();
        $beforeCount = AuditLog::count();

        $this->getJson('/api/v1/admin/dashboard');

        $this->assertEquals($beforeCount, AuditLog::count());
    }

    public function test_audit_log_contains_ip_and_user_agent(): void
    {
        $this->authAdmin();
        $config = SystemConfig::factory()->create();

        $this->putJson('/api/v1/admin/system-configs/batch', [
            'configs' => [['id' => $config->id, 'config_value' => 'test']],
        ]);

        $log = AuditLog::latest()->first();
        $this->assertNotNull($log->ip);
        $this->assertNotNull($log->user_agent);
    }

    public function test_unauthenticated_request_does_not_create_audit_log(): void
    {
        $beforeCount = AuditLog::count();

        $this->getJson('/api/v1/admin/dashboard');

        $this->assertEquals($beforeCount, AuditLog::count());
    }
}
