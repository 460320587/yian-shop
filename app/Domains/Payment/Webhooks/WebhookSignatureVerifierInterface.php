<?php

declare(strict_types=1);

namespace App\Domains\Payment\Webhooks;

use Illuminate\Http\Request;

/**
 * Webhook 签名验证接口
 *
 * 各支付网关（微信/支付宝/银联）实现此接口以验证回调请求的签名。
 * 验证通过返回 true，失败抛出 SecurityException。
 */
interface WebhookSignatureVerifierInterface
{
    /**
     * 获取验证器名称
     */
    public function getGateway(): string;

    /**
     * 验证请求签名
     *
     * @param Request $request 原始 HTTP 请求
     * @return bool 验证通过返回 true
     * @throws \Illuminate\Validation\ValidationException 签名验证失败
     */
    public function verify(Request $request): bool;
}
