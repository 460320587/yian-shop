<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\Admin\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_creates_super_admin(): void
    {
        $this->seed(\Database\Seeders\AdminRoleSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);

        $this->assertDatabaseCount('admins', 1);
        $this->assertDatabaseHas('admins', [
            'username' => 'admin',
            'real_name' => '超级管理员',
            'status' => 1,
        ]);
    }

    public function test_super_admin_has_valid_password(): void
    {
        $this->seed(\Database\Seeders\AdminRoleSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);

        $admin = Admin::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('admin123', $admin->password));
    }

    public function test_super_admin_has_super_role(): void
    {
        $this->seed(\Database\Seeders\AdminRoleSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);

        $admin = Admin::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($admin->role_id);
        $this->assertEquals('super', $admin->roleModel->code);
    }
}
