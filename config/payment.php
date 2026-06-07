<?php

/**
 * 支付系统配置
 */
return [
    /**
     * 是否使用真实支付网关
     *
     * - false（默认）：所有网关返回 Mock，用于开发和测试环境
     * - true：使用真实的微信/支付宝网关，需要配置下方对应参数
     */
    'use_real_gateway' => (bool) env('PAYMENT_USE_REAL_GATEWAY', false),

    /**
     * Webhook 签名验证模式
     *
     * - skip: 跳过验签（开发环境默认）
     * - strict: 严格验签，要求请求头包含正确的签名（模拟真实环境）
     */
    'webhook_verify_mode' => env('PAYMENT_WEBHOOK_VERIFY_MODE', 'skip'),

    /**
     * 微信支付 V3 配置
     */
    'wechatpay' => [
        'mch_id' => env('WECHATPAY_MCH_ID', ''),
        'app_id' => env('WECHATPAY_APP_ID', ''),
        'api_v3_key' => env('WECHATPAY_API_V3_KEY', ''),
        'serial_no' => env('WECHATPAY_SERIAL_NO', ''),
        'private_key' => env('WECHATPAY_PRIVATE_KEY', ''),
        'public_key' => env('WECHATPAY_PUBLIC_KEY', ''),
        'notify_url' => env('WECHATPAY_NOTIFY_URL', ''),
    ],

    /**
     * 支付宝配置
     */
    'alipay' => [
        'app_id' => env('ALIPAY_APP_ID', ''),
        'private_key' => env('ALIPAY_PRIVATE_KEY', ''),
        'public_key' => env('ALIPAY_PUBLIC_KEY', ''),
        'alipay_public_key' => env('ALIPAY_ALIPAY_PUBLIC_KEY', ''),
        'notify_url' => env('ALIPAY_NOTIFY_URL', ''),
    ],
];
