<?php

declare(strict_types=1);

return [
    'api_prefix' => env('APP_API_PREFIX', 'api/v1'),
    'admin_prefix' => env('APP_ADMIN_PREFIX', 'admin'),
    'cors_origins' => explode(',', env('APP_CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),

    'upload' => [
        'max_size' => 100 * 1024 * 1024, // 100MB
        'allowed_extensions' => ['pdf', 'ai', 'psd', 'cdr', 'jpg', 'jpeg', 'png', 'zip', 'rar', '7z'],
        'chunk_size' => 2 * 1024 * 1024, // 2MB
    ],

    'order' => [
        'auto_cancel_minutes' => 30,
        'deposit_ratios' => [0.3, 0.5, 0.7, 1.0],
    ],

    'vip' => [
        'levels' => [
            0 => ['name' => '普通会员', 'discount' => 1.0, 'sample_discount' => 1.0],
            1 => ['name' => 'Lv1', 'discount' => 0.95, 'sample_discount' => 0.9],
            2 => ['name' => 'Lv2', 'discount' => 0.92, 'sample_discount' => 0.9],
            3 => ['name' => 'Lv3', 'discount' => 0.90, 'sample_discount' => 0.9],
            4 => ['name' => 'Lv4', 'discount' => 0.88, 'sample_discount' => 1.0],
            5 => ['name' => 'Lv5', 'discount' => 0.85, 'sample_discount' => 0.5],
            6 => ['name' => 'Lv6', 'discount' => 0.82, 'sample_discount' => 1.0],
            7 => ['name' => 'Lv7', 'discount' => 0.80, 'sample_discount' => 1.0],
            8 => ['name' => 'Lv8', 'discount' => 0.75, 'sample_discount' => 1.0],
        ],
    ],
];
