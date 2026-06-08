<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "mock", "aliyun"
    |
    */

    'default' => env('SMS_DRIVER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | SMS Drivers
    |--------------------------------------------------------------------------
    */

    'drivers' => [
        'mock' => [
            // 模拟驱动，仅记录日志，不实际发送短信
        ],

        'aliyun' => [
            'access_key_id' => env('ALIBABA_CLOUD_ACCESS_KEY_ID'),
            'access_key_secret' => env('ALIBABA_CLOUD_ACCESS_KEY_SECRET'),
            'sign_name' => env('SMS_SIGN_NAME'),
            'endpoint' => env('SMS_ENDPOINT', 'dysmsapi.aliyuncs.com'),
        ],
    ],
];
