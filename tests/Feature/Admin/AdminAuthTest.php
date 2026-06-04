<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('Admin@123456'),
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'username' => 'admin',
            'password' => 'Admin@123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.admin.username', 'admin');
        $data = $response->json('data');
        $this->assertArrayHasKey('token', $data);
    }

    public function test_admin_login_fails_with_wrong_password(): void
    {
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('Admin@123456'),
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', ErrorCode::AUTH_LOGIN_FAILED->value);
    }

    public function test_disabled_admin_cannot_login(): void
    {
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('Admin@123456'),
            'status' => 0,
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'username' => 'admin',
            'password' => 'Admin@123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', ErrorCode::USER_DISABLED->value);
    }

    public function test_admin_can_get_profile(): void
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $token = $admin->createToken('admin-api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/admin/auth/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.username', $admin->username);
    }

    public function test_admin_can_logout(): void
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $token = $admin->createToken('admin-api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/admin/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);
    }

    public function test_unauthenticated_admin_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/v1/admin/auth/profile');

        $response->assertStatus(401);
    }
}
