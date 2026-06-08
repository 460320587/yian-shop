<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriverInterface;
use RuntimeException;

class AliyunSmsDriver implements SmsDriverInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function send(string $phone, string $templateCode, array $params): bool
    {
        // TODO: 接入阿里云短信 SDK
        // 需要配置：access_key_id, access_key_secret, sign_name
        // 示例集成方式（待实现）：
        // 1. 安装阿里云 SDK: composer require alibabacloud/dysmsapi-20170525
        // 2. 使用 SDK 发送短信
        // 3. 处理发送结果和异常
        throw new RuntimeException(
            '阿里云短信驱动尚未实现，请完善 config/sms.php 配置并接入阿里云 SDK。' .
            '配置项：access_key_id, access_key_secret, sign_name'
        );
    }

    public function getName(): string
    {
        return 'aliyun';
    }
}
