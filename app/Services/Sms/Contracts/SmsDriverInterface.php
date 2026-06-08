<?php

declare(strict_types=1);

namespace App\Services\Sms\Contracts;

interface SmsDriverInterface
{
    /**
     * 发送短信
     *
     * @param string $phone 手机号
     * @param string $templateCode 短信模板码
     * @param array<string, mixed> $params 模板参数
     * @return bool 是否发送成功
     */
    public function send(string $phone, string $templateCode, array $params): bool;

    /**
     * 获取驱动名称
     */
    public function getName(): string;
}
