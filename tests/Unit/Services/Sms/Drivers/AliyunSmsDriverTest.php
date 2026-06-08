<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Sms\Drivers;

use App\Services\Sms\Drivers\AliyunSmsDriver;
use RuntimeException;
use Tests\TestCase;

class AliyunSmsDriverTest extends TestCase
{
    public function test_aliyun_driver_name_is_aliyun(): void
    {
        $driver = new AliyunSmsDriver([
            'access_key_id' => 'test_id',
            'access_key_secret' => 'test_secret',
            'sign_name' => 'test_sign',
        ]);

        $this->assertSame('aliyun', $driver->getName());
    }

    public function test_aliyun_driver_throws_when_not_configured(): void
    {
        $driver = new AliyunSmsDriver([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('阿里云短信驱动尚未实现');

        $driver->send('13800138000', 'SMS_VERIFY', ['code' => '123456']);
    }
}
