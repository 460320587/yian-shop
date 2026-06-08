<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsDriverInterface;
use App\Services\Sms\Drivers\AliyunSmsDriver;
use App\Services\Sms\Drivers\MockSmsDriver;
use InvalidArgumentException;

class SmsDriverFactory
{
    /**
     * 创建短信驱动实例
     */
    public static function make(?string $driver = null): SmsDriverInterface
    {
        $driver ??= config('sms.default', 'mock');
        $config = config("sms.drivers.{$driver}", []);

        return match ($driver) {
            'mock' => new MockSmsDriver($config),
            'aliyun' => new AliyunSmsDriver($config),
            default => throw new InvalidArgumentException("不支持的短信驱动: {$driver}"),
        };
    }
}
