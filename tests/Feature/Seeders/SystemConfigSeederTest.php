<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\System\Models\SystemConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemConfigSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_config_seeder_creates_configs(): void
    {
        $this->seed(\Database\Seeders\SystemConfigSeeder::class);

        $this->assertDatabaseCount('system_configs', 6);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'site_name']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'site_logo']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'contact_phone']);
        $this->assertDatabaseHas('system_configs', ['config_key' => 'icp备案号']);
    }

    public function test_system_config_seeder_has_basic_group(): void
    {
        $this->seed(\Database\Seeders\SystemConfigSeeder::class);

        $basicCount = SystemConfig::where('group', 'basic')->count();
        $this->assertGreaterThanOrEqual(2, $basicCount);
    }
}
