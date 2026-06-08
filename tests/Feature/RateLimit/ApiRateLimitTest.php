<?php

declare(strict_types=1);

namespace Tests\Feature\RateLimit;

use App\Domains\Portal\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 清除所有限流计数器
        RateLimiterFacade::clear('api:*');
        Cache::flush();
    }

    public function test_api_global_rate_limit_blocks_excessive_requests(): void
    {
        Banner::factory()->create(['position' => 'home', 'status' => 1]);

        // 快速发送超过限制的请求
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/v1/portal/banners');
        }

        // 最后一个请求应被限流
        $response->assertStatus(429)
            ->assertJsonPath('message', '请求过于频繁，请稍后再试');
    }

    public function test_login_endpoint_rate_limit_blocks_brute_force(): void
    {
        // 快速发送 6 次登录请求（密码需满足验证规则 min:6）
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'phone' => '13800138000',
                'password' => 'wrong_password',
            ]);
        }

        // 第 6 次应被限流
        $response->assertStatus(429)
            ->assertJsonPath('message', '登录失败次数过多，请15分钟后再试');
    }

    public function test_webhook_endpoint_has_higher_rate_limit(): void
    {
        // webhook 应该有独立的更高限流阈值（如 30req/min）
        // 先发送 30 次不应被限流
        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
                'out_trade_no' => 'NONEXISTENT',
                'trade_state' => 'SUCCESS',
            ]);
        }

        // 第 30 次由于支付单不存在返回 FAIL，但不应被限流
        $response->assertStatus(200);
    }

    public function test_rate_limit_resets_after_decay_period(): void
    {
        Banner::factory()->create(['position' => 'home', 'status' => 1]);

        // 触发限流
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/v1/portal/banners');
        }
        $response->assertStatus(429);

        // 模拟时间衰减（清除计数器）
        RateLimiterFacade::clear('rate_limit:api:' . $this->getClientIp());

        // 再次请求应成功
        $response = $this->getJson('/api/v1/portal/banners');
        $response->assertStatus(200);
    }

    public function test_rate_limit_uses_user_id_when_authenticated(): void
    {
        $customer = \App\Domains\User\Models\Customer::factory()->create();
        $token = $customer->createToken('api')->plainTextToken;

        // 认证用户请求 65 次
        for ($i = 0; $i < 65; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson('/api/v1/user/profile');
        }

        $response->assertStatus(429);
    }

    private function getClientIp(): string
    {
        return '127.0.0.1';
    }
}
