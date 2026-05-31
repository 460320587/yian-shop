<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => '退出成功',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $customer->id,
        ]);
    }

    public function test_guest_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
            ->assertJson([
                'code' => 401,
                'message' => '未登录或登录已过期',
            ]);
    }

    public function test_authenticated_user_can_refresh_token(): void
    {
        $customer = Customer::factory()->create();
        $oldToken = $customer->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $oldToken)
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'token',
                    'user',
                ],
            ])
            ->assertJsonPath('code', 0);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $oldToken)[1] ?? $oldToken),
        ]);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '13800138000',
            'nickname' => '测试用户',
        ]);

        Sanctum::actingAs($customer, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.phone', '13800138000')
            ->assertJsonPath('data.nickname', '测试用户');
    }

    public function test_guest_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/v1/user/profile');

        $response->assertStatus(401)
            ->assertJson([
                'code' => 401,
                'message' => '未登录或登录已过期',
            ]);
    }
}
