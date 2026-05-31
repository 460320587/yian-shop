<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => '13800138000',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'nickname' => '测试用户',
            'link_person' => '联系人',
            'qq' => '123456789',
        ]);

        $response->assertStatus(201)
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

        $this->assertDatabaseHas('customers', [
            'phone' => '13800138000',
            'nickname' => '测试用户',
        ]);
    }

    public function test_register_requires_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422)
            ->assertJsonPath('data.phone', ['手机号必填']);
    }

    public function test_register_requires_valid_phone_format(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => 'invalid',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.phone', ['手机号格式不正确']);
    }

    public function test_register_requires_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => '13800138000',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.password', ['密码必填']);
    }

    public function test_register_password_must_be_confirmed(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => '13800138000',
            'password' => 'Password123',
            'password_confirmation' => 'Different456',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.password', ['两次密码输入不一致']);
    }

    public function test_register_password_must_be_strong(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => '13800138000',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.password', ['密码长度至少8位，需包含字母和数字']);
    }

    public function test_register_phone_must_be_unique(): void
    {
        Customer::factory()->create(['phone' => '13800138000']);

        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => '13800138000',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.phone', ['该手机号已被注册']);
    }
}
