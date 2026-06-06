<?php

declare(strict_types=1);

namespace App\Services;

use App\Domains\Notification\Models\SmsLog;
use App\Exceptions\BusinessException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Cache;

class SmsCodeService
{
    private const CACHE_PREFIX = 'sms_code:';
    private const LOCK_PREFIX = 'sms_code_lock:';
    private const DAILY_PREFIX = 'sms_code_daily:';
    private const CODE_TTL_SECONDS = 300; // 5 minutes
    private const LOCK_TTL_SECONDS = 60;  // 60 seconds
    private const DAILY_LIMIT = 10;
    private const CODE_LENGTH = 6;

    public function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    public function send(string $phone, string $captchaKey, string $captchaCode): void
    {
        $this->validateCaptcha($captchaKey, $captchaCode);
        $this->checkRateLimit($phone);

        $code = $this->generateCode();

        Cache::put(self::CACHE_PREFIX . $phone, $code, now()->addSeconds(self::CODE_TTL_SECONDS));
        Cache::put(self::LOCK_PREFIX . $phone, true, now()->addSeconds(self::LOCK_TTL_SECONDS));
        $this->incrementDailyCount($phone);

        $this->recordSmsLog($phone, $code);
        $this->dispatchSms($phone, $code);
    }

    public function verify(string $phone, string $code): bool
    {
        $key = self::CACHE_PREFIX . $phone;
        $expected = Cache::get($key);

        if ($expected === null || $expected !== $code) {
            return false;
        }

        Cache::forget($key);

        return true;
    }

    private function validateCaptcha(string $captchaKey, string $captchaCode): void
    {
        $expected = Cache::get($captchaKey);

        if ($expected === null || strtoupper($expected) !== strtoupper($captchaCode)) {
            throw new BusinessException(ErrorCode::BAD_REQUEST, '验证码不正确');
        }

        Cache::forget($captchaKey);
    }

    private function checkRateLimit(string $phone): void
    {
        if (Cache::has(self::LOCK_PREFIX . $phone)) {
            throw new BusinessException(ErrorCode::SMS_SEND_TOO_FREQUENT, '短信发送过于频繁，请稍后再试');
        }

        $dailyCount = (int) Cache::get(self::DAILY_PREFIX . $phone, 0);
        if ($dailyCount >= self::DAILY_LIMIT) {
            throw new BusinessException(ErrorCode::SMS_SEND_TOO_FREQUENT, '今日短信发送次数已达上限');
        }
    }

    private function incrementDailyCount(string $phone): void
    {
        $key = self::DAILY_PREFIX . $phone;
        $count = (int) Cache::get($key, 0);

        if ($count === 0) {
            Cache::put($key, 1, now()->endOfDay());
        } else {
            Cache::increment($key);
        }
    }

    private function recordSmsLog(string $phone, string $code): void
    {
        SmsLog::create([
            'phone' => $phone,
            'template_code' => 'SMS_VERIFY',
            'content' => '验证码：' . $code . '，5分钟内有效',
            'type' => 1,
            'status' => 1,
            'provider' => 'mock',
            'ip_address' => request()->ip(),
        ]);
    }

    private function dispatchSms(string $phone, string $code): void
    {
        // TODO: 接入真实短信网关（阿里云/腾讯云）
        // 当前为 Mock 实现，仅记录日志
    }
}
