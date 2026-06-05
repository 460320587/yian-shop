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

// 门户与首页 (Phase 10)
Route::prefix('portal')->group(function () {
    Route::get('/banners', [\App\Http\Controllers\Api\PortalController::class, 'banners']);
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'tree']);
    Route::get('/announcements', [\App\Http\Controllers\Api\PortalController::class, 'announcements']);
    Route::get('/hot-products', [\App\Http\Controllers\Api\PortalController::class, 'hotProducts']);
    Route::get('/new-arrivals', [\App\Http\Controllers\Api\PortalController::class, 'newArrivals']);
    Route::get('/home', [\App\Http\Controllers\Api\PortalController::class, 'home']);
});

// 认证模块 (Phase 2)
Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/refresh', [\App\Http\Controllers\Api\AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::post('/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword']);
    Route::get('/captcha', [\App\Http\Controllers\Api\AuthController::class, 'captcha']);
});

// 用户中心
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\Api\AuthController::class, 'profile']);
    Route::put('/profile', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
});

// 地址管理 (Phase 6)
Route::middleware('auth:sanctum')->prefix('addresses')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\AddressController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\AddressController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\Api\AddressController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\AddressController::class, 'destroy']);
    Route::put('/{id}/default', [\App\Http\Controllers\Api\AddressController::class, 'setDefault']);
});

// 商品系统 (Phase 3)
Route::prefix('products')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::post('/{id}/price', [\App\Http\Controllers\Api\ProductController::class, 'price']);
    Route::get('/{id}/params', [\App\Http\Controllers\Api\ProductController::class, 'params']);
    Route::get('/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'indexByProduct']);
});

// 分类系统 (Phase 3)
Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'tree']);

// 购物车 (Phase 4)
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\CartController::class, 'index']);
    Route::post('/items', [\App\Http\Controllers\Api\CartController::class, 'store']);
    Route::put('/items/{id}', [\App\Http\Controllers\Api\CartController::class, 'update']);
    Route::delete('/items/{id}', [\App\Http\Controllers\Api\CartController::class, 'destroyItem']);
    Route::delete('/', [\App\Http\Controllers\Api\CartController::class, 'clear']);
    Route::post('/checkout', [\App\Http\Controllers\Api\CartController::class, 'checkout']);
});

// 订单系统 (Phase 4)
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::put('/{id}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
    Route::post('/{id}/reorder', [\App\Http\Controllers\Api\OrderController::class, 'reorder']);
    Route::post('/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
});

// 支付系统 (Phase 5)
Route::middleware('auth:sanctum')->prefix('payments')->group(function () {
    Route::post('/create', [\App\Http\Controllers\Api\PaymentController::class, 'create']);
    Route::get('/{id}/status', [\App\Http\Controllers\Api\PaymentController::class, 'status']);
    Route::post('/wallet/recharge', [\App\Http\Controllers\Api\PaymentController::class, 'recharge']);
    Route::post('/wallet/withdraw', [\App\Http\Controllers\Api\PaymentController::class, 'withdraw']);
    Route::post('/{id}/mock-callback', [\App\Http\Controllers\Api\PaymentController::class, 'mockCallback']);
});

// 物流 (Phase 6)
Route::middleware('auth:sanctum')->prefix('logistics')->group(function () {
    Route::get('/{orderId}/tracks', [\App\Http\Controllers\Api\LogisticsController::class, 'tracks']);
    Route::get('/{orderId}/recommend', [\App\Http\Controllers\Api\LogisticsController::class, 'recommend']);
});

// 售后 (Phase 6)
Route::middleware('auth:sanctum')->prefix('after-sales')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\AfterSaleController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\AfterSaleController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\AfterSaleController::class, 'show']);
    Route::put('/{id}/cancel', [\App\Http\Controllers\Api\AfterSaleController::class, 'cancel']);
});

// 发票抬头 (Phase 11)
Route::middleware('auth:sanctum')->prefix('invoice-titles')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\InvoiceController::class, 'titleIndex']);
    Route::post('/', [\App\Http\Controllers\Api\InvoiceController::class, 'titleStore']);
    Route::put('/{id}', [\App\Http\Controllers\Api\InvoiceController::class, 'titleUpdate']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\InvoiceController::class, 'titleDestroy']);
});

// 发票申请 (Phase 11)
Route::middleware('auth:sanctum')->prefix('invoices')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\InvoiceController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\InvoiceController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\InvoiceController::class, 'show']);
    Route::put('/{id}/cancel', [\App\Http\Controllers\Api\InvoiceController::class, 'cancel']);
});

// VIP体系 (Phase 7)
Route::middleware('auth:sanctum')->prefix('vip')->group(function () {
    Route::get('/info', [\App\Http\Controllers\Api\VipController::class, 'info']);
    Route::get('/levels', [\App\Http\Controllers\Api\VipController::class, 'levels']);
    Route::get('/discounts', [\App\Http\Controllers\Api\VipController::class, 'discounts']);
});

// 消息通知 (Phase 6)
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::put('/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);
    Route::put('/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
    Route::get('/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
});

// 企业认证 (Phase 2)
Route::middleware('auth:sanctum')->prefix('enterprise')->group(function () {
    Route::get('/auth-status', [\App\Http\Controllers\Api\EnterpriseController::class, 'authStatus']);
    Route::post('/apply', [\App\Http\Controllers\Api\EnterpriseController::class, 'apply']);
    Route::get('/info', [\App\Http\Controllers\Api\EnterpriseController::class, 'info']);
});

// 收藏与复购 (Phase 7)
Route::middleware('auth:sanctum')->prefix('favorites')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\FavoriteController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\FavoriteController::class, 'store']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\FavoriteController::class, 'destroy']);
});

// 样品订单 (Phase 7)
Route::middleware('auth:sanctum')->prefix('samples')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\SampleController::class, 'index']);
    Route::post('/orders', [\App\Http\Controllers\Api\SampleController::class, 'store']);
    Route::get('/orders/{id}', [\App\Http\Controllers\Api\SampleController::class, 'show']);
});

// 积分 (Phase 7)
Route::middleware('auth:sanctum')->prefix('points')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\PointsController::class, 'index']);
    Route::get('/records', [\App\Http\Controllers\Api\PointsController::class, 'records']);
});

// 工单投诉 (Phase 12)
Route::middleware('auth:sanctum')->prefix('tickets')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\TicketController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\TicketController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\TicketController::class, 'show']);
    Route::put('/{id}/cancel', [\App\Http\Controllers\Api\TicketController::class, 'cancel']);
});

// 优惠券 (Phase 13)
Route::middleware('auth:sanctum')->prefix('coupons')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\CouponController::class, 'index']);
    Route::post('/{id}/claim', [\App\Http\Controllers\Api\CouponController::class, 'claim']);
});
Route::middleware('auth:sanctum')->get('/my-coupons', [\App\Http\Controllers\Api\CouponController::class, 'myCoupons']);
Route::middleware('auth:sanctum')->get('/my-reviews', [\App\Http\Controllers\Api\ReviewController::class, 'myReviews']);

// Admin 后台 (Admin Phase)
Route::prefix('admin')->group(function () {
    Route::post('/auth/login', [\App\Http\Controllers\Api\Admin\AdminAuthController::class, 'login']);

    Route::middleware(['auth:admin', \App\Http\Middleware\AuditLogMiddleware::class])->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\Admin\AdminAuthController::class, 'logout']);
        Route::get('/auth/profile', [\App\Http\Controllers\Api\Admin\AdminAuthController::class, 'profile']);

        // 商品管理
        Route::get('/products', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'index']);
        Route::get('/products/{id}', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'show']);
        Route::post('/products', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'store']);
        Route::put('/products/{id}', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'update']);
        Route::put('/products/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'toggleStatus']);

        // 客户管理
        Route::get('/customers', [\App\Http\Controllers\Api\Admin\AdminCustomerController::class, 'index']);
        Route::get('/customers/{id}', [\App\Http\Controllers\Api\Admin\AdminCustomerController::class, 'show']);

        // 订单管理
        Route::get('/orders', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'show']);
        Route::put('/orders/{id}/confirm-payment', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'confirmPayment']);
        Route::put('/orders/{id}/ship', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'ship']);
        Route::put('/orders/{id}/complete', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'complete']);

        // Banner/公告管理
        Route::get('/banners', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'bannerIndex']);
        Route::post('/banners', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'bannerStore']);
        Route::put('/banners/{id}', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'bannerUpdate']);
        Route::delete('/banners/{id}', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'bannerDestroy']);
        Route::get('/announcements', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'announcementIndex']);
        Route::post('/announcements', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'announcementStore']);
        Route::put('/announcements/{id}', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'announcementUpdate']);
        Route::delete('/announcements/{id}', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'announcementDestroy']);

        // 售后审核
        Route::get('/after-sales', [\App\Http\Controllers\Api\Admin\AdminAfterSaleController::class, 'index']);
        Route::get('/after-sales/{id}', [\App\Http\Controllers\Api\Admin\AdminAfterSaleController::class, 'show']);
        Route::put('/after-sales/{id}/audit', [\App\Http\Controllers\Api\Admin\AdminAfterSaleController::class, 'audit']);

        // 发票管理
        Route::get('/invoices', [\App\Http\Controllers\Api\Admin\AdminInvoiceController::class, 'index']);
        Route::get('/invoices/{id}', [\App\Http\Controllers\Api\Admin\AdminInvoiceController::class, 'show']);
        Route::put('/invoices/{id}/audit', [\App\Http\Controllers\Api\Admin\AdminInvoiceController::class, 'audit']);
        Route::put('/invoices/{id}/issue', [\App\Http\Controllers\Api\Admin\AdminInvoiceController::class, 'issue']);

        // 工单管理
        Route::get('/tickets', [\App\Http\Controllers\Api\Admin\AdminTicketController::class, 'index']);
        Route::get('/tickets/{id}', [\App\Http\Controllers\Api\Admin\AdminTicketController::class, 'show']);
        Route::put('/tickets/{id}/process', [\App\Http\Controllers\Api\Admin\AdminTicketController::class, 'process']);

        // 数据看板
        Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'index']);

        // 审计日志
        Route::get('/audit-logs', [\App\Http\Controllers\Api\Admin\AdminAuditLogController::class, 'index']);
        Route::get('/audit-logs/{id}', [\App\Http\Controllers\Api\Admin\AdminAuditLogController::class, 'show']);

        // 系统配置
        Route::get('/system-configs', [\App\Http\Controllers\Api\Admin\AdminSystemConfigController::class, 'index']);
        Route::put('/system-configs/batch', [\App\Http\Controllers\Api\Admin\AdminSystemConfigController::class, 'batchUpdate']);
        Route::get('/system-configs/{id}', [\App\Http\Controllers\Api\Admin\AdminSystemConfigController::class, 'show']);
        Route::put('/system-configs/{id}', [\App\Http\Controllers\Api\Admin\AdminSystemConfigController::class, 'update']);

        // 优惠券管理
        Route::get('/coupons', [\App\Http\Controllers\Api\Admin\AdminCouponController::class, 'index']);
        Route::post('/coupons', [\App\Http\Controllers\Api\Admin\AdminCouponController::class, 'store']);
        Route::put('/coupons/{id}', [\App\Http\Controllers\Api\Admin\AdminCouponController::class, 'update']);
        Route::put('/coupons/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminCouponController::class, 'toggleStatus']);
    });
});

