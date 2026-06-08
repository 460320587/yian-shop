<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Sms\Drivers;

use App\Services\Sms\Drivers\MockSmsDriver;
use Tests\TestCase;

class MockSmsDriverTest extends TestCase
{
    public function test_mock_driver_returns_true_on_send(): void
    {
        $driver = new MockSmsDriver([]);

        $result = $driver->send('13800138000', 'SMS_VERIFY', ['code' => '123456']);

        $this->assertTrue($result);
    }

    public function test_mock_driver_name_is_mock(): void
    {
        $driver = new MockSmsDriver([]);

        $this->assertSame('mock', $driver->getName());
    }
}
