<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\System\Models\SystemConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemConfigTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_system_configs(): void
    {
        $this->authAdmin();
        SystemConfig::factory()->create(['config_key' => 'site_name', 'config_value' => '怡安印刷', 'group' => 'basic']);
        SystemConfig::factory()->create(['config_key' => 'maintenance_mode', 'config_value' => '0', 'group' => 'system']);

        $response = $this->getJson('/api/v1/admin/system-configs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'config_key', 'config_value', 'type', 'description', 'group'],
                ],
            ]);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_filter_by_group(): void
    {
        $this->authAdmin();
        SystemConfig::factory()->create(['config_key' => 'site_name', 'group' => 'basic']);
        SystemConfig::factory()->create(['config_key' => 'maintenance_mode', 'group' => 'system']);

        $response = $this->getJson('/api/v1/admin/system-configs?group=basic');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('site_name', $response->json('data.0.config_key'));
    }

    public function test_admin_can_view_config_detail(): void
    {
        $this->authAdmin();
        $config = SystemConfig::factory()->create(['config_key' => 'site_name', 'config_value' => '怡安印刷']);

        $response = $this->getJson("/api/v1/admin/system-configs/{$config->id}");

        $response->assertOk()
            ->assertJsonPath('data.config_key', 'site_name')
            ->assertJsonPath('data.config_value', '怡安印刷');
    }

    public function test_admin_can_update_config(): void
    {
        $this->authAdmin();
        $config = SystemConfig::factory()->create(['config_key' => 'site_name', 'config_value' => '旧名称']);

        $response = $this->putJson("/api/v1/admin/system-configs/{$config->id}", [
            'config_value' => '新名称',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('system_configs', ['id' => $config->id, 'config_value' => '新名称']);
    }

    public function test_admin_can_batch_update_configs(): void
    {
        $this->authAdmin();
        $config1 = SystemConfig::factory()->create(['config_key' => 'site_name', 'config_value' => '旧']);
        $config2 = SystemConfig::factory()->create(['config_key' => 'site_logo', 'config_value' => '旧']);

        $response = $this->putJson('/api/v1/admin/system-configs/batch', [
            'configs' => [
                ['id' => $config1->id, 'config_value' => '新名称'],
                ['id' => $config2->id, 'config_value' => '新Logo'],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('system_configs', ['id' => $config1->id, 'config_value' => '新名称']);
        $this->assertDatabaseHas('system_configs', ['id' => $config2->id, 'config_value' => '新Logo']);
    }

    public function test_unauthenticated_cannot_access_system_configs(): void
    {
        $this->getJson('/api/v1/admin/system-configs')
            ->assertUnauthorized();
    }
}
