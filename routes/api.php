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
    Route::post('/sms-code', [\App\Http\Controllers\Api\AuthController::class, 'sendSmsCode']);
    Route::post('/login-sms', [\App\Http\Controllers\Api\AuthController::class, 'loginSms']);
    Route::get('/check-phone', [\App\Http\Controllers\Api\AuthController::class, 'checkPhone']);
});

// 用户中心
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\Api\AuthController::class, 'profile']);
    Route::put('/profile', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
});

// 支付密码
Route::middleware('auth:sanctum')->prefix('pay-password')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\CustomerPayPasswordController::class, 'store']);
    Route::put('/', [\App\Http\Controllers\Api\CustomerPayPasswordController::class, 'update']);
    Route::post('/verify', [\App\Http\Controllers\Api\CustomerPayPasswordController::class, 'verify']);
    Route::get('/status', [\App\Http\Controllers\Api\CustomerPayPasswordController::class, 'status']);
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
    Route::post('/pricing', [\App\Http\Controllers\Api\OrderController::class, 'pricing']);
    Route::post('/', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::get('/{id}/status-logs', [\App\Http\Controllers\Api\OrderController::class, 'statusLogs']);
    Route::put('/{id}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
    Route::post('/{id}/reorder', [\App\Http\Controllers\Api\OrderController::class, 'reorder']);
    Route::post('/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
    Route::get('/{id}/files', [\App\Http\Controllers\Api\OrderController::class, 'files']);
    Route::post('/{id}/files', [\App\Http\Controllers\Api\OrderController::class, 'uploadFile']);
    Route::get('/{id}/production-schedule', [\App\Http\Controllers\Api\OrderController::class, 'productionSchedule']);
    Route::get('/{id}/ink-coverage-checks', [\App\Http\Controllers\Api\OrderController::class, 'inkCoverageChecks']);
    Route::delete('/{id}/files/{fileId}', [\App\Http\Controllers\Api\OrderController::class, 'deleteFile']);
});

// 支付系统 (Phase 5)
Route::middleware('auth:sanctum')->prefix('payments')->group(function () {
    Route::post('/create', [\App\Http\Controllers\Api\PaymentController::class, 'create']);
    Route::get('/{id}/status', [\App\Http\Controllers\Api\PaymentController::class, 'status']);
    Route::post('/wallet/recharge', [\App\Http\Controllers\Api\PaymentController::class, 'recharge']);
    Route::post('/wallet/withdraw', [\App\Http\Controllers\Api\PaymentController::class, 'withdraw']);
    Route::post('/{id}/mock-callback', [\App\Http\Controllers\Api\PaymentController::class, 'mockCallback']);
});

// 支付回调（无需认证）
Route::post('/webhooks/wechat-pay', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'wechatPay']);
Route::post('/webhooks/alipay', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'alipay']);

// 钱包 (Phase 5)
Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
    Route::get('/balance', [\App\Http\Controllers\Api\PaymentController::class, 'balance']);
    Route::get('/transactions', [\App\Http\Controllers\Api\PaymentController::class, 'transactions']);
});

// 上传 (Phase 6)
Route::middleware('auth:sanctum')->prefix('upload')->group(function () {
    Route::post('/review-images', [\App\Http\Controllers\Api\UploadController::class, 'reviewImages']);
});

// 物流 (Phase 6)
Route::middleware('auth:sanctum')->prefix('logistics')->group(function () {
    Route::get('/{orderId}/tracks', [\App\Http\Controllers\Api\LogisticsController::class, 'tracks']);
    Route::get('/{orderId}/map', [\App\Http\Controllers\Api\LogisticsController::class, 'map']);
    Route::get('/{orderId}/recommend', [\App\Http\Controllers\Api\LogisticsController::class, 'recommend']);
});

// 售后 (Phase 6)
Route::middleware('auth:sanctum')->prefix('after-sales')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\AfterSaleController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\AfterSaleController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\AfterSaleController::class, 'show']);
    Route::put('/{id}/cancel', [\App\Http\Controllers\Api\AfterSaleController::class, 'cancel']);
});

// 退款记录 (Phase 6)
Route::middleware('auth:sanctum')->prefix('refunds')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\RefundRecordController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\RefundRecordController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\RefundRecordController::class, 'show']);
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
    Route::delete('/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
});

// 内容系统 (Phase 10)
Route::prefix('articles')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ContentController::class, 'articles']);
    Route::get('/{slug}', [\App\Http\Controllers\Api\ContentController::class, 'show']);
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

        // 分类管理
        Route::get('/categories', [\App\Http\Controllers\Api\Admin\AdminCategoryController::class, 'index']);
        Route::post('/categories', [\App\Http\Controllers\Api\Admin\AdminCategoryController::class, 'store']);
        Route::put('/categories/{id}', [\App\Http\Controllers\Api\Admin\AdminCategoryController::class, 'update']);
        Route::delete('/categories/{id}', [\App\Http\Controllers\Api\Admin\AdminCategoryController::class, 'destroy']);
        Route::put('/categories/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminCategoryController::class, 'toggleStatus']);

        // 商品管理
        Route::get('/products', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'index']);
        Route::get('/products/{id}', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'show']);
        Route::post('/products', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'store']);
        Route::put('/products/{id}', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'update']);
        Route::put('/products/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminProductController::class, 'toggleStatus']);

        // 客户管理
        Route::get('/customers', [\App\Http\Controllers\Api\Admin\AdminCustomerController::class, 'index']);
        Route::get('/customers/{id}', [\App\Http\Controllers\Api\Admin\AdminCustomerController::class, 'show']);
        Route::put('/customers/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminCustomerController::class, 'toggleStatus']);

        // 企业认证审核
        Route::get('/enterprise-auths', [\App\Http\Controllers\Api\Admin\AdminEnterpriseAuthController::class, 'index']);
        Route::get('/enterprise-auths/{id}', [\App\Http\Controllers\Api\Admin\AdminEnterpriseAuthController::class, 'show']);
        Route::put('/enterprise-auths/{id}/audit', [\App\Http\Controllers\Api\Admin\AdminEnterpriseAuthController::class, 'audit']);

        // 订单管理
        Route::get('/orders', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'show']);
        Route::put('/orders/{id}/confirm-payment', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'confirmPayment']);
        Route::put('/orders/{id}/ship', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'ship']);
        Route::put('/orders/{id}/complete', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'complete']);
        Route::get('/orders/{id}/files', [\App\Http\Controllers\Api\Admin\AdminOrderFileController::class, 'index']);
        Route::get('/orders/{id}/ink-coverage-checks', [\App\Http\Controllers\Api\Admin\AdminOrderFileController::class, 'inkCoverageChecks']);

        // 订单文件管理
        Route::delete('/order-files/{id}', [\App\Http\Controllers\Api\Admin\AdminOrderFileController::class, 'destroy']);

        // 印前检查管理
        Route::get('/ink-coverage-checks', [\App\Http\Controllers\Api\Admin\AdminInkCoverageCheckController::class, 'index']);
        Route::post('/ink-coverage-checks', [\App\Http\Controllers\Api\Admin\AdminInkCoverageCheckController::class, 'store']);
        Route::get('/ink-coverage-checks/{id}', [\App\Http\Controllers\Api\Admin\AdminOrderFileController::class, 'showInkCoverageCheck']);
        Route::put('/ink-coverage-checks/{id}', [\App\Http\Controllers\Api\Admin\AdminInkCoverageCheckController::class, 'update']);
        Route::delete('/ink-coverage-checks/{id}', [\App\Http\Controllers\Api\Admin\AdminInkCoverageCheckController::class, 'destroy']);

        // 生产排期管理
        Route::get('/production-schedules', [\App\Http\Controllers\Api\Admin\AdminProductionScheduleController::class, 'index']);
        Route::post('/production-schedules', [\App\Http\Controllers\Api\Admin\AdminProductionScheduleController::class, 'store']);
        Route::get('/production-schedules/{id}', [\App\Http\Controllers\Api\Admin\AdminProductionScheduleController::class, 'show']);
        Route::put('/production-schedules/{id}', [\App\Http\Controllers\Api\Admin\AdminProductionScheduleController::class, 'update']);
        Route::put('/production-schedules/{id}/progress', [\App\Http\Controllers\Api\Admin\AdminProductionScheduleController::class, 'updateProgress']);

        // 样品订单管理
        Route::get('/sample-orders', [\App\Http\Controllers\Api\Admin\AdminSampleOrderController::class, 'index']);
        Route::get('/sample-orders/{id}', [\App\Http\Controllers\Api\Admin\AdminSampleOrderController::class, 'show']);
        Route::put('/sample-orders/{id}/status', [\App\Http\Controllers\Api\Admin\AdminSampleOrderController::class, 'updateStatus']);
        Route::delete('/sample-orders/{id}', [\App\Http\Controllers\Api\Admin\AdminSampleOrderController::class, 'destroy']);

        // Banner/公告管理
        Route::get('/banners', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'bannerIndex']);
        Route::post('/banners', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'bannerStore']);
        Route::put('/banners/{id}', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'bannerUpdate']);
        Route::delete('/banners/{id}', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'bannerDestroy']);
        Route::get('/announcements', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'announcementIndex']);
        Route::post('/announcements', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'announcementStore']);
        Route::put('/announcements/{id}', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'announcementUpdate']);
        Route::delete('/announcements/{id}', [\App\Http\Controllers\Api\Admin\AdminBannerController::class, 'announcementDestroy']);

        // 退款审核
        Route::get('/refunds', [\App\Http\Controllers\Api\Admin\AdminRefundController::class, 'index']);
        Route::get('/refunds/{id}', [\App\Http\Controllers\Api\Admin\AdminRefundController::class, 'show']);
        Route::put('/refunds/{id}/audit', [\App\Http\Controllers\Api\Admin\AdminRefundController::class, 'audit']);

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

        // 评价管理
        Route::get('/reviews', [\App\Http\Controllers\Api\Admin\AdminReviewController::class, 'index']);
        Route::get('/reviews/{id}', [\App\Http\Controllers\Api\Admin\AdminReviewController::class, 'show']);
        Route::put('/reviews/{id}/reply', [\App\Http\Controllers\Api\Admin\AdminReviewController::class, 'reply']);
        Route::put('/reviews/{id}/toggle-show', [\App\Http\Controllers\Api\Admin\AdminReviewController::class, 'toggleShow']);
        Route::delete('/reviews/{id}', [\App\Http\Controllers\Api\Admin\AdminReviewController::class, 'destroy']);

        // 优惠券管理
        Route::get('/coupons', [\App\Http\Controllers\Api\Admin\AdminCouponController::class, 'index']);
        Route::post('/coupons', [\App\Http\Controllers\Api\Admin\AdminCouponController::class, 'store']);
        Route::put('/coupons/{id}', [\App\Http\Controllers\Api\Admin\AdminCouponController::class, 'update']);
        Route::put('/coupons/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminCouponController::class, 'toggleStatus']);

        // 角色权限管理 (RBAC)
        Route::get('/roles', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'index']);
        Route::post('/roles', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'store']);
        Route::put('/roles/{id}', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'update']);
        Route::delete('/roles/{id}', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'destroy']);
        Route::put('/roles/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'toggleStatus']);
        Route::get('/roles/{id}/permissions', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'permissions']);
        Route::put('/roles/{id}/permissions', [\App\Http\Controllers\Api\Admin\AdminRoleController::class, 'assignPermissions']);
        Route::get('/permissions', [\App\Http\Controllers\Api\Admin\AdminPermissionController::class, 'index']);

        // 管理员账号管理
        Route::get('/admins', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'index']);
        Route::post('/admins', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'store']);
        Route::put('/admins/{id}', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'update']);
        Route::put('/admins/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'toggleStatus']);
        Route::delete('/admins/{id}', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'destroy']);
        Route::put('/admins/{id}/reset-password', [\App\Http\Controllers\Api\Admin\AdminAccountController::class, 'resetPassword']);

        // 参数模板管理
        Route::get('/param-templates', [\App\Http\Controllers\Api\Admin\AdminParamTemplateController::class, 'index']);
        Route::get('/param-templates/{id}', [\App\Http\Controllers\Api\Admin\AdminParamTemplateController::class, 'show']);

        // 文章管理
        Route::get('/articles', [\App\Http\Controllers\Api\Admin\AdminArticleController::class, 'index']);
        Route::post('/articles', [\App\Http\Controllers\Api\Admin\AdminArticleController::class, 'store']);
        Route::get('/articles/{id}', [\App\Http\Controllers\Api\Admin\AdminArticleController::class, 'show']);
        Route::put('/articles/{id}', [\App\Http\Controllers\Api\Admin\AdminArticleController::class, 'update']);
        Route::delete('/articles/{id}', [\App\Http\Controllers\Api\Admin\AdminArticleController::class, 'destroy']);
        Route::put('/articles/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\AdminArticleController::class, 'toggleStatus']);
    });
});

