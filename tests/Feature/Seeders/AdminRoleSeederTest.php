<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\Admin\Models\AdminPermission;
use App\Domains\Admin\Models\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminRoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_seeder_creates_roles(): void
    {
        $this->seed(\Database\Seeders\AdminRoleSeeder::class);

        $this->assertDatabaseCount('admin_roles', 3);
        $this->assertDatabaseHas('admin_roles', ['code' => 'super', 'name' => '超级管理员']);
        $this->assertDatabaseHas('admin_roles', ['code' => 'operator', 'name' => '运营']);
        $this->assertDatabaseHas('admin_roles', ['code' => 'finance', 'name' => '财务']);
    }

    public function test_admin_role_seeder_creates_permissions(): void
    {
        $this->seed(\Database\Seeders\AdminRoleSeeder::class);

        $this->assertDatabaseCount('admin_permissions', 12);
        $this->assertDatabaseHas('admin_permissions', ['code' => 'user:view', 'group' => '用户管理']);
        $this->assertDatabaseHas('admin_permissions', ['code' => 'order:view', 'group' => '订单管理']);
    }

    public function test_admin_role_seeder_assigns_permissions_to_super_role(): void
    {
        $this->seed(\Database\Seeders\AdminRoleSeeder::class);

        $role = AdminRole::where('code', 'super')->first();
        $this->assertNotNull($role);
        $this->assertGreaterThanOrEqual(10, $role->permissions()->count());
    }
}
