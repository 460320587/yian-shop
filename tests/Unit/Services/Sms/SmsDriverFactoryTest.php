<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Sms;

use App\Services\Sms\Drivers\AliyunSmsDriver;
use App\Services\Sms\Drivers\MockSmsDriver;
use App\Services\Sms\SmsDriverFactory;
use InvalidArgumentException;
use Tests\TestCase;

class SmsDriverFactoryTest extends TestCase
{
    public function test_factory_creates_mock_driver(): void
    {
        $driver = SmsDriverFactory::make('mock');

        $this->assertInstanceOf(MockSmsDriver::class, $driver);
        $this->assertSame('mock', $driver->getName());
    }

    public function test_factory_creates_aliyun_driver(): void
    {
        config(['sms.drivers.aliyun' => ['access_key_id' => 'test']]);

        $driver = SmsDriverFactory::make('aliyun');

        $this->assertInstanceOf(AliyunSmsDriver::class, $driver);
        $this->assertSame('aliyun', $driver->getName());
    }

    public function test_factory_throws_for_unknown_driver(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SmsDriverFactory::make('unknown');
    }
}
