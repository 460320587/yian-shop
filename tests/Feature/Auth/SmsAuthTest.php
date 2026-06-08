<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SmsAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_send_sms_code_with_valid_captcha(): void
    {
        Cache::put('test_key', '1234', now()->addMinutes(5));

        $response = $this->postJson('/api/v1/auth/sms-code', [
            'phone' => '13800138000',
            'captcha_key' => 'test_key',
            'captcha_code' => '1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '验证码已发送');

        $this->assertDatabaseHas('sms_logs', [
            'phone' => '13800138000',
            'type' => 1,
        ]);
    }

    public function test_send_sms_code_fails_without_captcha(): void
    {
        $response = $this->postJson('/api/v1/auth/sms-code', [
            'phone' => '13800138000',
            'captcha_key' => 'bad_key',
            'captcha_code' => '9999',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('message', '验证码不正确');
    }

    public function test_send_sms_code_rate_limited(): void
    {
        Cache::put('captcha_key_1', 'ABCD', now()->addMinutes(5));
        $this->postJson('/api/v1/auth/sms-code', [
            'phone' => '13800138000',
            'captcha_key' => 'captcha_key_1',
            'captcha_code' => 'ABCD',
        ])->assertStatus(200);

        Cache::forget('sms_code_lock:13800138000');
        Cache::put('captcha_key_2', 'ABCD', now()->addMinutes(5));

        for ($i = 2; $i <= 10; $i++) {
            Cache::forget('sms_code_lock:13800138000');
            RateLimiter::clear('sms_code:13800138000');
            RateLimiter::clear('sms_rate:13800138000');
            Cache::put("captcha_key_{$i}", 'ABCD', now()->addMinutes(5));
            $this->postJson('/api/v1/auth/sms-code', [
                'phone' => '13800138000',
                'captcha_key' => "captcha_key_{$i}",
                'captcha_code' => 'ABCD',
            ])->assertStatus(200);
        }

        Cache::forget('sms_code_lock:13800138000');
        Cache::put('captcha_key_overflow', 'ABCD', now()->addMinutes(5));
        $response = $this->postJson('/api/v1/auth/sms-code', [
            'phone' => '13800138000',
            'captcha_key' => 'captcha_key_overflow',
            'captcha_code' => 'ABCD',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('code', 1010)
            ->assertJsonPath('message', '今日短信发送次数已达上限');
    }

    public function test_user_can_login_with_sms_code(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '13800138000',
            'password' => 'hashed',
        ]);

        Cache::put('sms_code:13800138000', '654321', now()->addMinutes(5));

        $response = $this->postJson('/api/v1/auth/login-sms', [
            'phone' => '13800138000',
            'sms_code' => '654321',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => ['token', 'user' => ['id', 'phone', 'nickname']],
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $customer->id,
            'tokenable_type' => Customer::class,
        ]);
    }

    public function test_sms_login_auto_registers_new_phone(): void
    {
        Cache::put('sms_code:13800138099', '111111', now()->addMinutes(5));

        $response = $this->postJson('/api/v1/auth/login-sms', [
            'phone' => '13800138099',
            'sms_code' => '111111',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.user.phone', '13800138099');

        $this->assertDatabaseHas('customers', [
            'phone' => '13800138099',
            'nickname' => '用户8099',
        ]);
    }

    public function test_sms_login_fails_with_wrong_code(): void
    {
        Cache::put('sms_code:13800138000', '123456', now()->addMinutes(5));

        $response = $this->postJson('/api/v1/auth/login-sms', [
            'phone' => '13800138000',
            'sms_code' => '999999',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('code', 1011)
            ->assertJsonPath('message', '短信验证码错误或已过期');
    }

    public function test_check_phone_returns_available_for_new_phone(): void
    {
        $response = $this->getJson('/api/v1/auth/check-phone?phone=13800138000');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.available', true);
    }

    public function test_check_phone_returns_unavailable_for_existing_phone(): void
    {
        Customer::factory()->create(['phone' => '13800138000']);

        $response = $this->getJson('/api/v1/auth/check-phone?phone=13800138000');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.available', false);
    }

    public function test_send_sms_code_validates_phone_format(): void
    {
        $response = $this->postJson('/api/v1/auth/sms-code', [
            'phone' => '123',
            'captcha_key' => 'key',
            'captcha_code' => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }

    public function test_login_sms_validates_sms_code_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login-sms', [
            'phone' => '13800138000',
            'sms_code' => '12',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);
    }
}
