<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create([
            'nickname' => '旧昵称',
            'avatar' => 'http://example.com/old.jpg',
            'link_person' => '旧联系人',
            'qq' => '123456',
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_update_nickname(): void
    {
        $customer = $this->authCustomer();

        $response = $this->putJson('/api/v1/user/profile', [
            'nickname' => '新昵称',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.nickname', '新昵称');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'nickname' => '新昵称',
        ]);
    }

    public function test_user_can_update_avatar(): void
    {
        $customer = $this->authCustomer();

        $response = $this->putJson('/api/v1/user/profile', [
            'avatar' => 'http://example.com/new.jpg',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.avatar', 'http://example.com/new.jpg');
    }

    public function test_user_can_update_link_person_and_qq(): void
    {
        $customer = $this->authCustomer();

        $response = $this->putJson('/api/v1/user/profile', [
            'link_person' => '张三',
            'qq' => '987654321',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.link_person', '张三')
            ->assertJsonPath('data.qq', '987654321');
    }

    public function test_user_can_update_multiple_fields_at_once(): void
    {
        $customer = $this->authCustomer();

        $response = $this->putJson('/api/v1/user/profile', [
            'nickname' => '全能架构师',
            'avatar' => 'https://cdn.example.com/avatar.png',
            'link_person' => '李四',
            'qq' => '111222333',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.nickname', '全能架构师')
            ->assertJsonPath('data.avatar', 'https://cdn.example.com/avatar.png')
            ->assertJsonPath('data.link_person', '李四')
            ->assertJsonPath('data.qq', '111222333');
    }

    public function test_nickname_cannot_exceed_max_length(): void
    {
        $this->authCustomer();

        $response = $this->putJson('/api/v1/user/profile', [
            'nickname' => str_repeat('a', 51),
        ]);

        $response->assertStatus(422);
    }

    public function test_avatar_must_be_valid_url(): void
    {
        $this->authCustomer();

        $response = $this->putJson('/api/v1/user/profile', [
            'avatar' => 'not-a-url',
        ]);

        $response->assertStatus(422);
    }

    public function test_guest_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/v1/user/profile', [
            'nickname' => '游客',
        ]);

        $response->assertStatus(401);
    }
}
