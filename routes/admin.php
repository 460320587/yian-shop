<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
| 前缀: /admin
| 独立于 api/v1 之外
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', fn () => ['todo' => 'admin-dashboard']);
    Route::get('/customers', fn () => ['todo' => 'admin-customers']);
    Route::get('/orders', fn () => ['todo' => 'admin-orders']);
    Route::get('/products', fn () => ['todo' => 'admin-products']);
    Route::get('/finance', fn () => ['todo' => 'admin-finance']);
});
