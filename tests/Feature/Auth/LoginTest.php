<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '13800138000',
            'password' => Hash::make('Password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138000',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'phone',
                        'nickname',
                    ],
                ],
            ])
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $customer->id,
            'tokenable_type' => Customer::class,
        ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        Customer::factory()->create([
            'phone' => '13800138000',
            'password' => Hash::make('Password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138000',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 1000,
                'message' => '手机号或密码错误',
            ]);
    }

    public function test_login_fails_with_nonexistent_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138000',
            'password' => 'Password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 1000,
                'message' => '手机号或密码错误',
            ]);
    }

    public function test_login_requires_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422)
            ->assertJsonPath('data.phone', ['手机号必填']);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138000',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422)
            ->assertJsonPath('data.password', ['密码必填']);
    }
}
