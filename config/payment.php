<?php

/**
 * 支付系统配置
 */
return [
    /**
     * Webhook 签名验证模式
     *
     * - skip: 跳过验签（开发环境默认）
     * - strict: 严格验签，要求请求头包含正确的签名（模拟真实环境）
     */
    'webhook_verify_mode' => env('PAYMENT_WEBHOOK_VERIFY_MODE', 'skip'),
];
