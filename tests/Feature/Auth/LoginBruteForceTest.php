<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginBruteForceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:*');
        RateLimiter::clear('sms_rate:*');
        Cache::flush();
    }

    public function test_login_fails_are_tracked_per_phone(): void
    {
        Customer::factory()->create([
            'phone' => '13800138000',
            'password' => bcrypt('correct_password'),
        ]);

        // 连续 5 次失败登录
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'phone' => '13800138000',
                'password' => 'wrong_password',
            ]);
            $response->assertStatus(401);
        }

        // 第 6 次应被锁定
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138000',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', '登录失败次数过多，请15分钟后再试');
    }

    public function test_successful_login_clears_failure_count(): void
    {
        Customer::factory()->create([
            'phone' => '13800138000',
            'password' => bcrypt('correct_password'),
        ]);

        // 3 次失败
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'phone' => '13800138000',
                'password' => 'wrong_password',
            ]);
        }

        // 1 次成功
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138000',
            'password' => 'correct_password',
        ]);
        $response->assertStatus(200);

        // 再次失败不应立即被锁定（因为成功登录清除了计数）
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138000',
            'password' => 'wrong_password',
        ]);
        $response->assertStatus(401);
    }

    public function test_brute_force_lock_is_phone_specific(): void
    {
        Customer::factory()->create([
            'phone' => '13800138000',
            'password' => bcrypt('password1'),
        ]);
        Customer::factory()->create([
            'phone' => '13800138001',
            'password' => bcrypt('password2'),
        ]);

        // 对手机号 A 连续失败 5 次
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'phone' => '13800138000',
                'password' => 'wrong_password',
            ]);
        }

        // 手机号 A 被锁定
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138000',
            'password' => 'wrong_password',
        ]);
        $response->assertStatus(429);

        // 手机号 B 不应受影响
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138001',
            'password' => 'wrong_password',
        ]);
        $response->assertStatus(401); // 不是 429
    }

    public function test_brute_force_lock_expires_after_15_minutes(): void
    {
        Customer::factory()->create([
            'phone' => '13800138000',
            'password' => bcrypt('correct_password'),
        ]);

        // 触发锁定
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'phone' => '13800138000',
                'password' => 'wrong',
            ]);
        }

        // 清除限流计数器（模拟 15 分钟过去）
        RateLimiter::clear('login:13800138000');

        // 应能再次尝试登录
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '13800138000',
            'password' => 'correct_password',
        ]);
        $response->assertStatus(200);
    }

    public function test_sms_code_endpoint_is_rate_limited(): void
    {
        // 先获取一个有效的图形验证码
        $captcha = $this->getJson('/api/v1/auth/captcha')->json('data');

        // 快速发送 4 次短信验证码请求
        for ($i = 0; $i < 4; $i++) {
            $response = $this->postJson('/api/v1/auth/sms-code', [
                'phone' => '13800138000',
                'captcha_key' => $captcha['captcha_key'],
                'captcha_code' => $captcha['captcha_code'],
            ]);
        }

        // 第 4 次应被限流（假设限制为 3 次/分钟）
        $response->assertStatus(429)
            ->assertJsonPath('message', '请求过于频繁，请稍后再试');
    }
}
