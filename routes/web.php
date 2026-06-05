<?php

use Illuminate\Support\Facades\Route;

// API 路由由 bootstrap/app.php 自动处理，不需要在这里定义

// SPA 入口：所有非 API 请求返回前端 index.html
Route::get('/{any?}', function () {
    return file_get_contents(public_path('build/index.html'));
})->where('any', '^(?!api|build|storage).*');
