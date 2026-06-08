<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriverInterface;

class MockSmsDriver implements SmsDriverInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function send(string $phone, string $templateCode, array $params): bool
    {
        // 模拟发送成功，不实际调用任何短信网关
        return true;
    }

    public function getName(): string
    {
        return 'mock';
    }
}
