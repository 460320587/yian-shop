<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domains\Notification\Models\SmsLog;
use App\Exceptions\BusinessException;
use App\Services\SmsCodeService;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SmsCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private SmsCodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SmsCodeService();
    }

    public function test_generate_code_is_6_digits(): void
    {
        $code = $this->service->generateCode();

        $this->assertIsString($code);
        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_send_validates_captcha(): void
    {
        Cache::put('captcha_key_123', 'ABCD', now()->addMinutes(5));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('验证码不正确');

        $this->service->send('13800138000', 'captcha_key_123', 'WRONG');
    }

    public function test_send_succeeds_with_valid_captcha(): void
    {
        Cache::put('captcha_key_123', 'ABCD', now()->addMinutes(5));

        $this->service->send('13800138000', 'captcha_key_123', 'ABCD');

        $this->assertDatabaseHas('sms_logs', [
            'phone' => '13800138000',
            'type' => 1,
            'status' => 1,
        ]);

        $this->assertTrue(Cache::has('sms_code:13800138000'));
    }

    public function test_send_rate_limited_by_60s_lock(): void
    {
        Cache::put('captcha_key_1', 'ABCD', now()->addMinutes(5));
        $this->service->send('13800138000', 'captcha_key_1', 'ABCD');

        Cache::put('captcha_key_2', 'ABCD', now()->addMinutes(5));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('短信发送过于频繁');

        $this->service->send('13800138000', 'captcha_key_2', 'ABCD');
    }

    public function test_send_daily_limit_10(): void
    {
        Cache::put('captcha_key_0', 'ABCD', now()->addMinutes(5));
        $this->service->send('13800138000', 'captcha_key_0', 'ABCD');

        for ($i = 1; $i < 10; $i++) {
            Cache::forget('sms_code_lock:13800138000');
            Cache::put("captcha_key_{$i}", 'ABCD', now()->addMinutes(5));
            $this->service->send('13800138000', "captcha_key_{$i}", 'ABCD');
        }

        $this->assertDatabaseCount('sms_logs', 10);

        Cache::forget('sms_code_lock:13800138000');
        Cache::put('captcha_key_overflow', 'ABCD', now()->addMinutes(5));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('今日短信发送次数已达上限');

        $this->service->send('13800138000', 'captcha_key_overflow', 'ABCD');
    }

    public function test_send_creates_sms_log(): void
    {
        Cache::put('captcha_key_123', 'ABCD', now()->addMinutes(5));

        $this->service->send('13800138000', 'captcha_key_123', 'ABCD');

        $this->assertDatabaseHas('sms_logs', [
            'phone' => '13800138000',
            'template_code' => 'SMS_VERIFY',
            'type' => 1,
            'status' => 1,
            'provider' => 'mock',
        ]);
    }

    public function test_verify_correct_code_returns_true_and_deletes(): void
    {
        Cache::put('sms_code:13800138000', '123456', now()->addMinutes(5));

        $result = $this->service->verify('13800138000', '123456');

        $this->assertTrue($result);
        $this->assertFalse(Cache::has('sms_code:13800138000'));
    }

    public function test_verify_wrong_code_returns_false(): void
    {
        Cache::put('sms_code:13800138000', '123456', now()->addMinutes(5));

        $result = $this->service->verify('13800138000', '999999');

        $this->assertFalse($result);
        $this->assertTrue(Cache::has('sms_code:13800138000'));
    }

    public function test_verify_expired_code_returns_false(): void
    {
        $result = $this->service->verify('13800138000', '123456');

        $this->assertFalse($result);
    }
}
