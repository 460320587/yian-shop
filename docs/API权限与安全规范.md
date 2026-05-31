# 怡安印刷商城 — API权限与安全规范（API Authorization & Security Specification）

> **版本**: v1.0  
> **日期**: 2026-05-30  
> **用途**: 后端开发可直接实现的权限控制、限流、幂等性、数据隔离规范  
> **技术栈**: PHP 8.5 + Laravel 13.x + Sanctum  
> **配套**: 与《API详细规范》配套使用

---

## 目录

1. [核心API权限映射表](#1-核心api权限映射表)
2. [Admin API权限映射表](#2-admin-api权限映射表)
3. [限流与熔断配置](#3-限流与熔断配置)
4. [幂等性实现规范](#4-幂等性实现规范)
5. [数据隔离规则](#5-数据隔离规则)
6. [CORS与安全头配置](#6-cors与安全头配置)
7. [Webhook签名验证](#7-webhook签名验证)

---

## 1. 核心API权限映射表

| API编号 | 方法 | 路由 | 认证 | 角色 | 权限码 | 数据隔离 | 限流 | 幂等 |
|:-------:|:----:|------|:----:|:----:|--------|----------|:----:|:----:|
| API-001 | POST | `/api/v1/auth/register` | ❌ | — | — | — | 5/min | ✅ |
| API-002 | POST | `/api/v1/auth/login` | ❌ | — | — | — | 5/min | ✅ |
| API-003 | POST | `/api/v1/auth/refresh` | ✅ | — | — | 本用户Token | 10/min | ❌ |
| API-004 | POST | `/api/v1/auth/logout` | ✅ | — | — | 本用户Token | 10/min | ✅ |
| API-005 | GET | `/api/v1/auth/captcha` | ❌ | — | — | — | 20/min | ❌ |
| API-005-A | POST | `/api/v1/admin/auth/login` | ❌ | — | — | — | 5/min | ✅ |
| API-006 | GET | `/api/v1/products` | ❌ | — | — | — | 100/min | ❌ |
| API-007 | GET | `/api/v1/products/{id}` | ❌ | — | — | — | 100/min | ❌ |
| API-008 | GET | `/api/v1/categories` | ❌ | — | — | — | 100/min | ❌ |
| API-009 | POST | `/api/v1/products/{id}/price` | ❌ | — | — | — | 200/min | ✅ |
| API-010 | GET | `/api/v1/cart` | ✅ | — | — | 本用户购物车 | 60/min | ❌ |
| API-011 | POST | `/api/v1/cart` | ✅ | — | — | 本用户购物车 | 60/min | ✅ |
| API-012 | PUT | `/api/v1/cart/{id}` | ✅ | — | — | 本用户购物车项 | 60/min | ✅ |
| API-013 | DELETE | `/api/v1/cart/{id}` | ✅ | — | — | 本用户购物车项 | 60/min | ✅ |
| API-014 | DELETE | `/api/v1/cart` | ✅ | — | — | 本用户购物车 | 10/min | ✅ |
| API-015 | POST | `/api/v1/orders` | ✅ | — | — | 本用户地址/购物车 | 10/min | ✅ |
| API-016 | GET | `/api/v1/orders` | ✅ | — | — | 本用户订单 | 60/min | ❌ |
| API-017 | GET | `/api/v1/orders/{order_no}` | ✅ | — | — | 本用户订单 | 60/min | ❌ |
| API-018 | POST | `/api/v1/orders/{order_no}/cancel` | ✅ | — | — | 本用户订单 | 10/min | ✅ |
| API-019 | POST | `/api/v1/payments` | ✅ | — | — | 本用户订单 | 10/min | ✅ |
| API-020 | GET | `/api/v1/payments/{id}` | ✅ | — | — | 本用户支付单 | 60/min | ❌ |
| API-021 | POST | `/api/v1/wallet/recharge` | ✅ | — | — | 本用户钱包 | 5/min | ✅ |
| API-022 | GET | `/api/v1/addresses` | ✅ | — | — | 本用户地址 | 60/min | ❌ |
| API-023 | POST | `/api/v1/addresses` | ✅ | — | — | 本用户地址 | 30/min | ✅ |
| API-024 | PUT | `/api/v1/addresses/{id}` | ✅ | — | — | 本用户地址 | 30/min | ✅ |
| API-025 | DELETE | `/api/v1/addresses/{id}` | ✅ | — | — | 本用户地址 | 30/min | ✅ |
| API-026 | GET | `/api/v1/notifications` | ✅ | — | — | 本用户通知 | 60/min | ❌ |
| API-027 | PUT | `/api/v1/notifications/{id}/read` | ✅ | — | — | 本用户通知 | 60/min | ✅ |
| API-028 | PUT | `/api/v1/notifications/read-all` | ✅ | — | — | 本用户通知 | 10/min | ✅ |

### 1.1 通用权限中间件

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class EnsureCustomerOwnership
{
    public function handle(Request $request, Closure $next, string $resource)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['code' => '1001', 'message' => '未登录'], 401);
        }

        // 数据隔离校验
        $resourceId = $request->route($resource);
        $model = match ($resource) {
            'order' => \App\Domains\Orders\Models\Order::class,
            'address' => \App\Domains\Customers\Models\Address::class,
            'cart_item' => \App\Domains\Orders\Models\CartItem::class,
            'notification' => \App\Domains\Notifications\Models\Notification::class,
            default => null,
        };

        if ($model) {
            $instance = $model::find($resourceId);
            if (! $instance || $instance->customer_id !== $user->id) {
                return response()->json(['code' => '4006', 'message' => '无权操作该资源'], 403);
            }
        }

        return $next($request);
    }
}
```

---

## 2. Admin API权限映射表

| API编号 | 方法 | 路由 | 角色 | 权限码 | 数据隔离 | 限流 |
|:-------:|:----:|------|:----:|--------|----------|:----:|
| AD-001 | GET | `/api/v1/admin/dashboard/stats` | super_admin, operator, finance | `report.dashboard` | super_admin全部/operator除财务/finance仅财务 | 60/min |
| AD-010 | GET | `/api/v1/admin/customers` | super_admin, operator, customer_service | `customer.view` | factory_manager仅本工厂客户 | 60/min |
| AD-011 | GET | `/api/v1/admin/customers/{id}` | super_admin, operator, customer_service | `customer.detail` | — | 60/min |
| AD-012 | PUT | `/api/v1/admin/customers/{id}/status` | super_admin, customer_service | `customer.update` | — | 30/min |
| AD-013 | PUT | `/api/v1/admin/customers/{id}/audit` | super_admin, operator | `customer.audit` | — | 30/min |
| AD-014 | GET | `/api/v1/admin/customers/{id}/orders` | super_admin, operator, customer_service | `customer.view` + `order.view` | — | 60/min |
| AD-020 | GET | `/api/v1/admin/orders` | super_admin, operator, customer_service, factory_manager, finance | `order.view` | factory_manager仅本工厂订单 | 60/min |
| AD-021 | GET | `/api/v1/admin/orders/{order_no}` | super_admin, operator, customer_service, factory_manager, finance | `order.detail` | factory_manager仅本工厂订单 | 60/min |
| AD-022 | PUT | `/api/v1/admin/orders/{order_no}/dispatch` | super_admin, operator | `order.dispatch` | — | 30/min |
| AD-023 | POST | `/api/v1/admin/orders/batch` | super_admin, operator | `order.batch` | — | 30/min |
| AD-024 | POST | `/api/v1/admin/orders/export` | super_admin, operator, finance | `order.export` | — | 10/min |
| AD-025 | GET | `/api/v1/admin/orders/export/{task_id}` | super_admin, operator, finance | `order.export` | — | 60/min |
| AD-030 | PUT | `/api/v1/admin/products/{id}/toggle` | super_admin, operator | `product.toggle` | — | 30/min |
| AD-031 | POST | `/api/v1/admin/products/batch` | super_admin, operator | `product.batch` | — | 30/min |
| AD-032 | CRUD | `/api/v1/admin/categories` | super_admin, operator | `category.manage` | — | 60/min |
| AD-040 | GET | `/api/v1/admin/finance/reconcile` | super_admin, finance | `finance.reconcile` | — | 60/min |
| AD-041 | GET | `/api/v1/admin/finance/refunds` | super_admin, finance | `finance.refund_audit` | — | 60/min |
| AD-042 | PUT | `/api/v1/admin/finance/refunds/{id}/audit` | super_admin, finance | `finance.refund_audit` | — | 30/min |
| AD-043 | GET | `/api/v1/admin/finance/invoices` | super_admin, finance | `finance.invoice_audit` | — | 60/min |
| AD-044 | GET | `/api/v1/admin/finance/reports` | super_admin, finance | `finance.report` | — | 30/min |
| AD-050 | GET | `/api/v1/admin/factories` | super_admin, operator, factory_manager | `factory.view` | factory_manager仅本工厂 | 60/min |
| AD-051 | GET | `/api/v1/admin/factories/{id}/schedule` | factory_manager | `factory.schedule` | 仅本工厂 | 60/min |
| AD-052 | PUT | `/api/v1/admin/factories/{id}/progress` | factory_manager | `factory.progress` | 仅本工厂 | 30/min |
| AD-060 | CRUD | `/api/v1/admin/banners` | super_admin, operator | `content.banner` | — | 60/min |
| AD-061 | CRUD | `/api/v1/admin/articles` | super_admin, operator | `content.article` | — | 60/min |
| AD-070 | CRUD | `/api/v1/admin/system/admins` | super_admin | `system.admin` | — | 60/min |
| AD-071 | CRUD | `/api/v1/admin/system/roles` | super_admin | `system.role` | — | 60/min |
| AD-072 | PUT | `/api/v1/admin/system/configs` | super_admin | `system.config` | — | 30/min |
| AD-073 | GET | `/api/v1/admin/system/audit-logs` | super_admin | `system.audit_log` | — | 60/min |
| AD-074 | GET | `/api/v1/admin/system/operation-logs` | super_admin | `system.operation_log` | — | 60/min |

### 2.1 Admin权限中间件

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user('admin');

        if (! $user) {
            return response()->json(['code' => '1001', 'message' => '未登录'], 401);
        }

        if (! $user->hasPermission($permission)) {
            return response()->json(['code' => '1009', 'message' => '无权限访问该资源'], 403);
        }

        // 数据隔离：factory_manager只能操作本工厂订单
        if ($user->role === 'factory_manager' && $request->route('order_no')) {
            $order = \App\Domains\Orders\Models\Order::where('order_no', $request->route('order_no'))->first();
            if (! $order || $order->factory_id !== $user->factory_id) {
                return response()->json(['code' => '1010', 'message' => '数据隔离限制'], 403);
            }
        }

        return $next($request);
    }
}
```

---

## 3. 限流与熔断配置

### 3.1 Laravel RateLimiter配置

```php
<?php

// bootstrap/app.php — withMiddleware
$middleware->alias([
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
]);

// app/Providers/AppServiceProvider.php — boot
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('payment', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('upload', function (Request $request) {
    return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
});
```

### 3.2 Nginx限流配置

```nginx
# 全局http段
limit_req_zone $binary_remote_addr zone=api:10m rate=100r/s;
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=upload:10m rate=20r/m;

# server段
location /api/v1/auth/login {
    limit_req zone=login burst=3 nodelay;
}

location /api/v1/upload {
    limit_req zone=upload burst=5 nodelay;
    client_max_body_size 500M;
}
```

---

## 4. 幂等性实现规范

### 4.1 幂等Key存储（Redis）

```php
<?php

namespace App\Domains\Common\Services;

use Illuminate\Support\Facades\Redis;

final readonly class IdempotencyService
{
    private const string KEY_PREFIX = 'idempotency:';
    private const int TTL = 86400; // 24小时

    public function checkAndStore(string $key, mixed $response): ?mixed
    {
        $redisKey = self::KEY_PREFIX . $key;
        $cached = Redis::get($redisKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        Redis::setex($redisKey, self::TTL, json_encode($response));
        return null;
    }

    public function isProcessed(string $key): bool
    {
        return Redis::exists(self::KEY_PREFIX . $key);
    }
}
```

### 4.2 中间件

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Domains\Common\Services\IdempotencyService;

final class Idempotency
{
    public function __construct(private IdempotencyService $service) {}

    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return $next($request);
        }

        // 检查是否已处理
        $cached = $this->service->checkAndStore($key, null);
        if ($cached !== null) {
            return response()->json($cached);
        }

        $response = $next($request);

        // 缓存成功响应
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->service->checkAndStore($key, json_decode($response->getContent(), true));
        }

        return $response;
    }
}
```

---

## 5. 数据隔离规则

### 5.1 隔离矩阵

| 角色 | 订单数据范围 | 客户数据范围 | 财务数据范围 | 生产数据范围 |
|------|-------------|-------------|-------------|-------------|
| `super_admin` | 全部 | 全部 | 全部 | 全部 |
| `operator` | 全部 | 全部 | 仅查看（无敏感字段） | 全部 |
| `customer_service` | 全部 | 全部 | 仅退款/发票相关 | 仅查看 |
| `factory_manager` | 分配工厂订单 | 分配工厂的客户 | 无 | 本工厂 |
| `finance` | 全部 | 全部 | 全部 | 无 |

### 5.2 Scope实现

```php
<?php

namespace App\Domains\Orders\Models;

use Illuminate\Database\Eloquent\Builder;

trait OrderScopes
{
    public function scopeForRole(Builder $query, Admin $admin): Builder
    {
        return match ($admin->role) {
            'factory_manager' => $query->where('factory_id', $admin->factory_id),
            default => $query,
        };
    }

    public function scopeWithFinancialFields(Builder $query, Admin $admin): Builder
    {
        return in_array($admin->role, ['super_admin', 'finance'])
            ? $query
            : $query->without(['cost_price', 'profit_margin']);
    }
}
```

---

## 6. CORS与安全头配置

```php
<?php

// config/cors.php
return [
    'paths' => ['api/*', 'admin/*', 'upload/*', 'webhooks/*'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'https://www.yian.com,https://admin.yian.com')),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['X-Request-ID'],
    'max_age' => 0,
    'supports_credentials' => true,
];

// config/secure-headers.php（或Nginx层面）
// X-Frame-Options: DENY
// X-Content-Type-Options: nosniff
// X-XSS-Protection: 1; mode=block
// Referrer-Policy: strict-origin-when-cross-origin
// Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'
// Strict-Transport-Security: max-age=31536000; includeSubDomains
```

---

## 7. Webhook签名验证

### 7.1 微信支付

```php
<?php

namespace App\Domains\Payments\Webhooks;

use Illuminate\Http\Request;

final class WechatPayWebhookVerifier
{
    public function verify(Request $request): bool
    {
        $signature = $request->header('Wechatpay-Signature');
        $timestamp = $request->header('Wechatpay-Timestamp');
        $nonce = $request->header('Wechatpay-Nonce');
        $serial = $request->header('Wechatpay-Serial');

        $message = "$timestamp\n$nonce\n" . $request->getContent() . "\n";
        $publicKey = $this->getPlatformPublicKey($serial);

        return openssl_verify($message, base64_decode($signature), $publicKey, 'sha256WithRSAEncryption') === 1;
    }
}
```

### 7.2 支付宝

```php
<?php

namespace App\Domains\Payments\Webhooks;

use Illuminate\Http\Request;

final class AlipayWebhookVerifier
{
    public function verify(Request $request): bool
    {
        $params = $request->all();
        $sign = $params['sign'];
        unset($params['sign'], $params['sign_type']);

        ksort($params);
        $stringToSign = urldecode(http_build_query($params));
        $publicKey = file_get_contents(storage_path('app/alipay_public_key.pem'));

        return openssl_verify($stringToSign, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }
}
```

---

*本文档为API安全与权限控制的完整实现参考，后端开发可直接复制中间件和配置代码到项目中。*
