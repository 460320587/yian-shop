<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 前缀已由 bootstrap/app.php 的 apiPrefix: 'api/v1' 定义
|
*/

// 健康检查
Route::get('/health', fn () => ['status' => 'ok', 'time' => now()->toDateTimeString()]);

// 门户与首页 (Phase 1 / Phase 3)
Route::prefix('portal')->group(function () {
    Route::get('/banners', fn () => ['todo' => 'banners']);
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'tree']);
    Route::get('/hot-products', fn () => ['todo' => 'hot-products']);
    Route::get('/new-arrivals', fn () => ['todo' => 'new-arrivals']);
});

// 认证模块 (Phase 2)
Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/refresh', [\App\Http\Controllers\Api\AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::post('/forgot-password', fn () => ['todo' => 'forgot-password']);
    Route::post('/reset-password', fn () => ['todo' => 'reset-password']);
    Route::get('/captcha', fn () => ['todo' => 'captcha']);
});

// 用户中心
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\Api\AuthController::class, 'profile']);
    Route::put('/profile', fn () => ['todo' => 'update-profile']);
    Route::get('/addresses', fn () => ['todo' => 'addresses']);
    Route::post('/addresses', fn () => ['todo' => 'create-address']);
    Route::put('/addresses/{id}', fn () => ['todo' => 'update-address']);
    Route::delete('/addresses/{id}', fn () => ['todo' => 'delete-address']);
});

// 商品系统 (Phase 3)
Route::prefix('products')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::get('/{id}/price', fn () => ['todo' => 'product-price']);
    Route::get('/{id}/params', fn () => ['todo' => 'product-params']);
});

// 分类系统 (Phase 3)
Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'tree']);

// 购物车 (Phase 4)
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', fn () => ['todo' => 'cart-list']);
    Route::post('/', fn () => ['todo' => 'add-cart']);
    Route::put('/{id}', fn () => ['todo' => 'update-cart']);
    Route::delete('/{id}', fn () => ['todo' => 'delete-cart']);
    Route::delete('/', fn () => ['todo' => 'clear-cart']);
    Route::post('/checkout', fn () => ['todo' => 'cart-checkout']);
});

// 订单系统 (Phase 4)
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/', fn () => ['todo' => 'order-list']);
    Route::post('/', fn () => ['todo' => 'create-order']);
    Route::get('/{id}', fn () => ['todo' => 'order-detail']);
    Route::put('/{id}/cancel', fn () => ['todo' => 'cancel-order']);
    Route::post('/{id}/reorder', fn () => ['todo' => 'reorder']);
});

// 支付系统 (Phase 5)
Route::middleware('auth:sanctum')->prefix('payments')->group(function () {
    Route::post('/create', fn () => ['todo' => 'create-payment']);
    Route::get('/{id}/status', fn () => ['todo' => 'payment-status']);
    Route::post('/wallet/recharge', fn () => ['todo' => 'wallet-recharge']);
    Route::post('/wallet/withdraw', fn () => ['todo' => 'wallet-withdraw']);
});

// 物流 (Phase 6)
Route::middleware('auth:sanctum')->prefix('logistics')->group(function () {
    Route::get('/{orderId}/tracks', fn () => ['todo' => 'logistics-tracks']);
    Route::get('/{orderId}/recommend', fn () => ['todo' => 'logistics-recommend']);
});

// 售后 (Phase 6)
Route::middleware('auth:sanctum')->prefix('after-sales')->group(function () {
    Route::get('/', fn () => ['todo' => 'after-sale-list']);
    Route::post('/', fn () => ['todo' => 'create-after-sale']);
    Route::get('/{id}', fn () => ['todo' => 'after-sale-detail']);
    Route::put('/{id}/cancel', fn () => ['todo' => 'cancel-after-sale']);
});

// 发票 (Phase 6)
Route::middleware('auth:sanctum')->prefix('invoices')->group(function () {
    Route::get('/', fn () => ['todo' => 'invoice-list']);
    Route::post('/', fn () => ['todo' => 'create-invoice']);
    Route::get('/{id}', fn () => ['todo' => 'invoice-detail']);
});

// VIP体系 (Phase 7)
Route::middleware('auth:sanctum')->prefix('vip')->group(function () {
    Route::get('/info', fn () => ['todo' => 'vip-info']);
    Route::get('/levels', fn () => ['todo' => 'vip-levels']);
    Route::get('/discounts', fn () => ['todo' => 'vip-discounts']);
});

// 消息通知 (Phase 8)
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', fn () => ['todo' => 'notification-list']);
    Route::put('/{id}/read', fn () => ['todo' => 'read-notification']);
    Route::put('/read-all', fn () => ['todo' => 'read-all-notifications']);
    Route::get('/unread-count', fn () => ['todo' => 'unread-count']);
});

// 企业认证 (Phase 2)
Route::middleware('auth:sanctum')->prefix('enterprise')->group(function () {
    Route::get('/auth-status', fn () => ['todo' => 'auth-status']);
    Route::post('/apply', fn () => ['todo' => 'apply-auth']);
    Route::get('/info', fn () => ['todo' => 'enterprise-info']);
});

// 收藏与复购 (Phase 7)
Route::middleware('auth:sanctum')->prefix('favorites')->group(function () {
    Route::get('/', fn () => ['todo' => 'favorite-list']);
    Route::post('/', fn () => ['todo' => 'add-favorite']);
    Route::delete('/{id}', fn () => ['todo' => 'remove-favorite']);
});

// 样品订单 (Phase 7)
Route::middleware('auth:sanctum')->prefix('samples')->group(function () {
    Route::get('/', fn () => ['todo' => 'sample-list']);
    Route::post('/orders', fn () => ['todo' => 'create-sample-order']);
    Route::get('/orders/{id}', fn () => ['todo' => 'sample-order-detail']);
});

// 积分 (Phase 7)
Route::middleware('auth:sanctum')->prefix('points')->group(function () {
    Route::get('/', fn () => ['todo' => 'points']);
    Route::get('/records', fn () => ['todo' => 'points-records']);
});

// 工单投诉 (Phase 10)
Route::middleware('auth:sanctum')->prefix('tickets')->group(function () {
    Route::get('/', fn () => ['todo' => 'ticket-list']);
    Route::post('/', fn () => ['todo' => 'create-ticket']);
    Route::get('/{id}', fn () => ['todo' => 'ticket-detail']);
});


