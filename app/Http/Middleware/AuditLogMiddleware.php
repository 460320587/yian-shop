<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Audit\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /** 敏感字段，记录时不保存 */
    private array $sensitiveFields = ['password', 'password_confirmation', 'token', 'credit_card'];

    /** 只记录的HTTP方法 */
    private array $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** Action 映射表 */
    private array $actionMap = [
        'store' => 'create',
        'create' => 'create',
        'update' => 'update',
        'edit' => 'update',
        'destroy' => 'delete',
        'delete' => 'delete',
        'toggleStatus' => 'update',
        'audit' => 'update',
        'issue' => 'update',
        'process' => 'update',
        'batchUpdate' => 'update',
        'bannerStore' => 'create',
        'bannerUpdate' => 'update',
        'bannerDestroy' => 'delete',
        'announcementStore' => 'create',
        'announcementUpdate' => 'update',
        'announcementDestroy' => 'delete',
        'login' => 'login',
        'logout' => 'logout',
    ];

    /** Model 映射表 */
    private array $modelMap = [
        'AdminBannerController' => 'Banner',
        'AdminCouponController' => 'Coupon',
        'AdminCustomerController' => 'Customer',
        'AdminOrderController' => 'Order',
        'AdminProductController' => 'Product',
        'AdminAfterSaleController' => 'AfterSale',
        'AdminInvoiceController' => 'Invoice',
        'AdminTicketController' => 'Ticket',
        'AdminSystemConfigController' => 'SystemConfig',
        'AdminAuthController' => 'Admin',
        'AdminDashboardController' => 'Dashboard',
        'AdminAuditLogController' => 'AuditLog',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!in_array($request->method(), $this->writeMethods, true)) {
            return $response;
        }

        $admin = $request->user('admin');
        if (!$admin) {
            return $response;
        }

        $route = $request->route();
        $action = $route?->getAction() ?? [];
        $controllerAction = $action['controller'] ?? '';

        // 解析 controller@action
        [$controller, $method] = $this->parseControllerAction($controllerAction);

        $modelType = $this->resolveModelType($controller);
        $actionType = $this->resolveActionType($method);
        $modelId = $request->route('id') ?? $request->route('orderNo') ?? null;

        $input = $request->except($this->sensitiveFields);
        $beforeData = $actionType === 'update' || $actionType === 'delete' ? $input : null;
        $afterData = $actionType === 'create' || $actionType === 'update' ? $input : null;

        AuditLog::create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->real_name ?: $admin->username,
            'action' => $actionType,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'result' => $response->isSuccessful() || $response->isRedirection() ? 1 : 0,
            'remark' => null,
        ]);

        return $response;
    }

    private function parseControllerAction(string $controllerAction): array
    {
        if (str_contains($controllerAction, '@')) {
            return explode('@', $controllerAction);
        }
        return [$controllerAction, ''];
    }

    private function resolveModelType(string $controller): string
    {
        $className = class_basename($controller);
        return $this->modelMap[$className] ?? 'Unknown';
    }

    private function resolveActionType(string $action): string
    {
        return $this->actionMap[$action] ?? $action;
    }
}
