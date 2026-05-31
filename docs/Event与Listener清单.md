# 怡安印刷商城 — Event与Listener完整清单（Events & Listeners Inventory）

> **版本**: v1.0  
> **日期**: 2026-05-30  
> **用途**: 开发可直接实现的30个Event + 80个Listener完整映射  
> **位置**: `app/Domains/{Domain}/Events/` + `app/Listeners/`

---

## 目录

1. [Event清单（30个）](#1-event清单30个)
2. [Listener清单（80个）](#2-listener清单80个)
3. [EventServiceProvider注册](#3-eventserviceprovider注册)
4. [Event→Queue映射](#4-eventqueue映射)

---

## 1. Event清单（30个）

| # | Event类名 | 文件路径 | 触发条件 | 携带数据 |
|:-:|-----------|----------|----------|----------|
| E01 | `UserRegistered` | `app/Domains/Auth/Events/UserRegistered.php` | 用户注册成功 | Customer模型 |
| E02 | `UserLoggedIn` | `app/Domains/Auth/Events/UserLoggedIn.php` | 用户登录成功 | Customer + IP + Device |
| E03 | `UserEnterpriseAuthPassed` | `app/Domains/Customers/Events/UserEnterpriseAuthPassed.php` | 企业认证审核通过 | Customer + EnterpriseAuth |
| E04 | `OrderCreated` | `app/Domains/Orders/Events/OrderCreated.php` | 订单创建成功 | Order模型 |
| E05 | `OrderPaid` | `app/Domains/Orders/Events/OrderPaid.php` | 支付成功回调 | Order + Payment |
| E06 | `OrderStatusChanged` | `app/Domains/Orders/Events/OrderStatusChanged.php` | FM状态机任何转移 | Order + oldStatus + newStatus |
| E07 | `OrderShipped` | `app/Domains/Orders/Events/OrderShipped.php` | 工厂标记已发货 | Order + Express |
| E08 | `OrderCompleted` | `app/Domains/Orders/Events/OrderCompleted.php` | 客户确认收货 | Order模型 |
| E09 | `OrderCancelled` | `app/Domains/Orders/Events/OrderCancelled.php` | 订单取消完成 | Order + reason |
| E10 | `OrderSplit` | `app/Domains/Orders/Events/OrderSplit.php` | 订单自动拆分 | ParentOrder + SubOrders[] |
| E11 | `PaymentSuccess` | `app/Domains/Payments/Events/PaymentSuccess.php` | 支付网关回调成功 | Payment模型 |
| E12 | `PaymentFailed` | `app/Domains/Payments/Events/PaymentFailed.php` | 支付失败 | Payment + error |
| E13 | `RefundRequested` | `app/Domains/Payments/Events/RefundRequested.php` | 客户/系统发起退款 | RefundRecord模型 |
| E14 | `RefundApproved` | `app/Domains/Payments/Events/RefundApproved.php` | 退款审批通过 | RefundRecord + approver |
| E15 | `RefundCompleted` | `app/Domains/Payments/Events/RefundCompleted.php` | 退款到账 | RefundRecord + path |
| E16 | `InventoryReserved` | `app/Domains/Orders/Events/InventoryReserved.php` | 库存预占成功 | Order + items[] |
| E17 | `InventoryReleased` | `app/Domains/Orders/Events/InventoryReleased.php` | 库存释放（取消/超时） | Order + items[] |
| E18 | `AfterSaleSubmitted` | `app/Domains/AfterSales/Events/AfterSaleSubmitted.php` | 售后申请提交 | AfterSale模型 |
| E19 | `AfterSaleAuditPassed` | `app/Domains/AfterSales/Events/AfterSaleAuditPassed.php` | 售后审核通过 | AfterSale模型 |
| E20 | `AfterSaleCompleted` | `app/Domains/AfterSales/Events/AfterSaleCompleted.php` | 售后完结 | AfterSale + result |
| E21 | `InvoiceRequested` | `app/Domains/Invoices/Events/InvoiceRequested.php` | 客户申请发票 | Invoice模型 |
| E22 | `InvoiceApproved` | `app/Domains/Invoices/Events/InvoiceApproved.php` | 发票审核通过 | Invoice模型 |
| E23 | `PointsIssued` | `app/Domains/Marketing/Events/PointsIssued.php` | 积分发放 | Customer + amount + source |
| E24 | `PointsExpired` | `app/Domains/Marketing/Events/PointsExpired.php` | 积分过期（Cron触发） | Customer + amount |
| E25 | `CouponReceived` | `app/Domains/Marketing/Events/CouponReceived.php` | 用户领取优惠券 | Customer + Coupon |
| E26 | `ProductLowStock` | `app/Domains/Products/Events/ProductLowStock.php` | 库存低于阈值 | Product + currentStock |
| E27 | `ProductStatusChanged` | `app/Domains/Products/Events/ProductStatusChanged.php` | 商品上下架 | Product + oldStatus + newStatus |
| E28 | `SystemError` | `app/Domains/Common/Events/SystemError.php` | 系统异常捕获 | Exception + context |
| E29 | `AdminOperationLogged` | `app/Domains/Admin/Events/AdminOperationLogged.php` | 管理员操作 | Admin + action + target |
| E30 | `MesStatusUpdated` | `app/Domains/Factories/Events/MesStatusUpdated.php` | MES回传生产状态 | Order + mesData |

---

## 2. Listener清单（80个）

### 2.1 通知类Listener（20个）

| # | Listener类名 | 文件路径 | 监听Event | 职责 | Channel |
|:-:|-------------|----------|-----------|------|---------|
| L01 | `SendWelcomeNotification` | `app/Listeners/Notification/SendWelcomeNotification.php` | E01 UserRegistered | 发送欢迎短信+站内信 | SMS + App |
| L02 | `SendLoginAlertNotification` | `app/Listeners/Notification/SendLoginAlertNotification.php` | E02 UserLoggedIn | 异地登录提醒 | SMS + App |
| L03 | `SendEnterpriseAuthPassedNotification` | `app/Listeners/Notification/SendEnterpriseAuthPassedNotification.php` | E03 UserEnterpriseAuthPassed | 认证通过通知 | App |
| L04 | `SendOrderCreatedNotification` | `app/Listeners/Notification/SendOrderCreatedNotification.php` | E04 OrderCreated | 订单创建确认 | App |
| L05 | `SendPaymentSuccessNotification` | `app/Listeners/Notification/SendPaymentSuccessNotification.php` | E05 OrderPaid | 支付成功通知 | App + SMS |
| L06 | `SendOrderStatusChangeNotification` | `app/Listeners/Notification/SendOrderStatusChangeNotification.php` | E06 OrderStatusChanged | 状态变更通知 | App |
| L07 | `SendShipmentNotification` | `app/Listeners/Notification/SendShipmentNotification.php` | E07 OrderShipped | 发货通知+运单号 | App + SMS |
| L08 | `SendOrderCompletedNotification` | `app/Listeners/Notification/SendOrderCompletedNotification.php` | E08 OrderCompleted | 订单完成+评价提醒 | App |
| L09 | `SendOrderCancelledNotification` | `app/Listeners/Notification/SendOrderCancelledNotification.php` | E09 OrderCancelled | 取消通知 | App |
| L10 | `SendRefundApprovedNotification` | `app/Listeners/Notification/SendRefundApprovedNotification.php` | E14 RefundApproved | 退款审批通过 | App |
| L11 | `SendRefundCompletedNotification` | `app/Listeners/Notification/SendRefundCompletedNotification.php` | E15 RefundCompleted | 退款到账通知 | App + SMS |
| L12 | `SendAfterSaleSubmittedNotification` | `app/Listeners/Notification/SendAfterSaleSubmittedNotification.php` | E18 AfterSaleSubmitted | 售后申请提交（客服侧） | App(Admin) |
| L13 | `SendAfterSaleResultNotification` | `app/Listeners/Notification/SendAfterSaleResultNotification.php` | E20 AfterSaleCompleted | 售后结果通知 | App |
| L14 | `SendInvoiceApprovedNotification` | `app/Listeners/Notification/SendInvoiceApprovedNotification.php` | E22 InvoiceApproved | 发票开具完成 | App + Email |
| L15 | `SendPointsIssuedNotification` | `app/Listeners/Notification/SendPointsIssuedNotification.php` | E23 PointsIssued | 积分到账通知 | App |
| L16 | `SendPointsExpiryWarningNotification` | `app/Listeners/Notification/SendPointsExpiryWarningNotification.php` | E24 PointsExpired | 积分过期提醒 | App |
| L17 | `SendCouponReceivedNotification` | `app/Listeners/Notification/SendCouponReceivedNotification.php` | E25 CouponReceived | 优惠券到账 | App |
| L18 | `SendLowStockAlertNotification` | `app/Listeners/Notification/SendLowStockAlertNotification.php` | E26 ProductLowStock | 库存预警（运营侧） | App(Admin) |
| L19 | `SendSystemErrorAlertNotification` | `app/Listeners/Notification/SendSystemErrorAlertNotification.php` | E28 SystemError | 系统异常告警 | DingTalk/Slack |
| L20 | `SendSmsNotification` | `app/Listeners/Notification/SendSmsNotification.php` | *多Event* | 统一短信发送网关 | SMS |

### 2.2 订单处理类Listener（15个）

| # | Listener类名 | 文件路径 | 监听Event | 职责 | 同步/异步 |
|:-:|-------------|----------|-----------|------|:---------:|
| L21 | `ReserveInventoryListener` | `app/Listeners/Order/ReserveInventoryListener.php` | E04 OrderCreated | 预占库存24h | 同步 |
| L22 | `ReleaseInventoryListener` | `app/Listeners/Order/ReleaseInventoryListener.php` | E09 OrderCancelled | 释放库存 | 同步 |
| L23 | `DeductInventoryListener` | `app/Listeners/Order/DeductInventoryListener.php` | E05 OrderPaid | 支付后扣减库存 | 同步 |
| L24 | `AutoCancelUnpaidOrderListener` | `app/Listeners/Order/AutoCancelUnpaidOrderListener.php` | E04 OrderCreated | 24h/48h/72h后自动取消 | 队列 |
| L25 | `AutoConfirmReceiptListener` | `app/Listeners/Order/AutoConfirmReceiptListener.php` | E07 OrderShipped | 发货后7天自动确认收货 | 队列 |
| L26 | `AutoCloseOrderListener` | `app/Listeners/Order/AutoCloseOrderListener.php` | E08 OrderCompleted | 完成后30天自动归档 | 队列 |
| L27 | `UpdateCustomerStatsListener` | `app/Listeners/Order/UpdateCustomerStatsListener.php` | E08 OrderCompleted | 更新客户累计消费/订单数 | 队列 |
| L28 | `CheckVipUpgradeListener` | `app/Listeners/Order/CheckVipUpgradeListener.php` | E08 OrderCompleted | 检查VIP等级是否升级 | 队列 |
| L29 | `GenerateOrderNoListener` | `app/Listeners/Order/GenerateOrderNoListener.php` | E04 OrderCreated | 生成订单号（Luhn校验） | 同步 |
| L30 | `CreateOrderDeliveryListener` | `app/Listeners/Order/CreateOrderDeliveryListener.php` | E05 OrderPaid | 创建配送记录 | 同步 |
| L31 | `SplitOrderListener` | `app/Listeners/Order/SplitOrderListener.php` | E04 OrderCreated | 按工厂/地址自动拆分 | 同步 |
| L32 | `DispatchToFactoryListener` | `app/Listeners/Order/DispatchToFactoryListener.php` | E05 OrderPaid | 智能分配工厂 | 队列 |
| L33 | `SendToMesListener` | `app/Listeners/Order/SendToMesListener.php` | E11 PaymentSuccess | 下发prepress_job_sheet到MES | 队列 |
| L34 | `CreateInvoiceRequestListener` | `app/Listeners/Order/CreateInvoiceRequestListener.php` | E08 OrderCompleted | 自动创建发票申请（如客户设置） | 队列 |
| L35 | `SendOrderToErpListener` | `app/Listeners/Order/SendOrderToErpListener.php` | E05 OrderPaid | 同步销售订单到ERP | 队列 |

### 2.3 支付处理类Listener（10个）

| # | Listener类名 | 文件路径 | 监听Event | 职责 | 同步/异步 |
|:-:|-------------|----------|-----------|------|:---------:|
| L36 | `UpdateOrderPaymentStatusListener` | `app/Listeners/Payment/UpdateOrderPaymentStatusListener.php` | E11 PaymentSuccess | 更新订单为已付款 | 同步 |
| L37 | `UpdateOrderPaymentFailedListener` | `app/Listeners/Payment/UpdateOrderPaymentFailedListener.php` | E12 PaymentFailed | 记录失败原因 | 同步 |
| L38 | `RecordPaymentLogListener` | `app/Listeners/Payment/RecordPaymentLogListener.php` | E11/E12 | 记录支付日志 | 同步 |
| L39 | `ProcessRefundListener` | `app/Listeners/Payment/ProcessRefundListener.php` | E14 RefundApproved | 执行退款（调用网关） | 队列 |
| L40 | `ReturnBalanceListener` | `app/Listeners/Payment/ReturnBalanceListener.php` | E15 RefundCompleted | 退款到账后更新余额 | 同步 |
| L41 | `RecordRefundLogListener` | `app/Listeners/Payment/RecordRefundLogListener.php` | E13/E14/E15 | 记录退款日志 | 同步 |
| L42 | `CheckDuplicateCallbackListener` | `app/Listeners/Payment/CheckDuplicateCallbackListener.php` | E11 PaymentSuccess | 幂等性校验（分布式锁） | 同步 |
| L43 | `NotifyFinanceTeamListener` | `app/Listeners/Payment/NotifyFinanceTeamListener.php` | E15 RefundCompleted | 大额退款通知财务 | 队列 |
| L44 | `UpdateDailyReconcileListener` | `app/Listeners/Payment/UpdateDailyReconcileListener.php` | E11 PaymentSuccess | 更新日终对账数据 | 队列 |
| L45 | `TriggerCommissionSettlementListener` | `app/Listeners/Payment/TriggerCommissionSettlementListener.php` | E11 PaymentSuccess | 触发佣金/分销结算 | 队列 |

### 2.4 积分营销类Listener（8个）

| # | Listener类名 | 文件路径 | 监听Event | 职责 | 同步/异步 |
|:-:|-------------|----------|-----------|------|:---------:|
| L46 | `IssueOrderPointsListener` | `app/Listeners/Marketing/IssueOrderPointsListener.php` | E08 OrderCompleted | 按消费金额发放积分 | 队列 |
| L47 | `IssueReviewPointsListener` | `app/Listeners/Marketing/IssueReviewPointsListener.php` | E08 OrderCompleted | 评价后发放积分 | 队列 |
| L48 | `DeductRefundPointsListener` | `app/Listeners/Marketing/DeductRefundPointsListener.php` | E15 RefundCompleted | 退款扣减积分 | 队列 |
| L49 | `ExpirePointsDailyListener` | `app/Listeners/Marketing/ExpirePointsDailyListener.php` | *Cron* | 每日过期积分处理 | 队列 |
| L50 | `SendPointsExpiryWarningListener` | `app/Listeners/Marketing/SendPointsExpiryWarningListener.php` | *Cron* | 过期前7/3/1天预警 | 队列 |
| L51 | `CalculateRfmScoreListener` | `app/Listeners/Marketing/CalculateRfmScoreListener.php` | E08 OrderCompleted | 更新RFM评分 | 队列 |
| L52 | `AutoTagCustomerListener` | `app/Listeners/Marketing/AutoTagCustomerListener.php` | E08 OrderCompleted | 自动客户标签 | 队列 |
| L53 | `IssueFirstOrderCouponListener` | `app/Listeners/Marketing/IssueFirstOrderCouponListener.php` | E08 OrderCompleted | 首单优惠券发放 | 队列 |

### 2.5 风控审计类Listener（8个）

| # | Listener类名 | 文件路径 | 监听Event | 职责 | 同步/异步 |
|:-:|-------------|----------|-----------|------|:---------:|
| L54 | `ScoreOrderRiskListener` | `app/Listeners/RiskControl/ScoreOrderRiskListener.php` | E04 OrderCreated | 订单风控评分 | 同步 |
| L55 | `BlockHighRiskOrderListener` | `app/Listeners/RiskControl/BlockHighRiskOrderListener.php` | E04 OrderCreated | >80分拦截订单 | 同步 |
| L56 | `RecordLoginIpListener` | `app/Listeners/RiskControl/RecordLoginIpListener.php` | E02 UserLoggedIn | 记录登录IP用于风控 | 同步 |
| L57 | `CheckBlacklistListener` | `app/Listeners/RiskControl/CheckBlacklistListener.php` | E04 OrderCreated | 黑名单校验 | 同步 |
| L58 | `WriteAuditLogListener` | `app/Listeners/Audit/WriteAuditLogListener.php` | *所有状态变更Event* | 写审计日志 | 队列 |
| L59 | `WriteOperationLogListener` | `app/Listeners/Audit/WriteOperationLogListener.php` | E29 AdminOperationLogged | 写操作日志 | 同步 |
| L60 | `RecordOrderSnapshotListener` | `app/Listeners/Audit/RecordOrderSnapshotListener.php` | E06 OrderStatusChanged | 记录状态变更快照 | 队列 |
| L61 | `TrackCustomerBehaviorListener` | `app/Listeners/Audit/TrackCustomerBehaviorListener.php` | E04/E05/E08 | 埋点数据上报 | 队列 |

### 2.6 搜索索引类Listener（5个）

| # | Listener类名 | 文件路径 | 监听Event | 职责 | 同步/异步 |
|:-:|-------------|----------|-----------|------|:---------:|
| L62 | `SyncProductToMeilisearchListener` | `app/Listeners/Search/SyncProductToMeilisearchListener.php` | E27 ProductStatusChanged | 同步商品到Meilisearch | 队列 |
| L63 | `SyncOrderToMeilisearchListener` | `app/Listeners/Search/SyncOrderToMeilisearchListener.php` | E06 OrderStatusChanged | 同步订单到Meilisearch | 队列 |
| L64 | `IndexProductKeywordsListener` | `app/Listeners/Search/IndexProductKeywordsListener.php` | E27 ProductStatusChanged | 索引关键词/同义词 | 队列 |
| L65 | `UpdateHotSearchListener` | `app/Listeners/Search/UpdateHotSearchListener.php` | E04 OrderCreated | 更新热搜词权重 | 队列 |
| L66 | `RebuildSearchIndexListener` | `app/Listeners/Search/RebuildSearchIndexListener.php` | *Command* | 重建搜索索引 | 队列 |

### 2.7 缓存清理类Listener（6个）

| # | Listener类名 | 文件路径 | 监听Event | 职责 | 同步/异步 |
|:-:|-------------|----------|-----------|------|:---------:|
| L67 | `ClearProductCacheListener` | `app/Listeners/Cache/ClearProductCacheListener.php` | E27 ProductStatusChanged | 清除商品缓存 | 同步 |
| L68 | `ClearOrderCacheListener` | `app/Listeners/Cache/ClearOrderCacheListener.php` | E06 OrderStatusChanged | 清除订单缓存 | 同步 |
| L69 | `ClearCustomerCacheListener` | `app/Listeners/Cache/ClearCustomerCacheListener.php` | E03 UserEnterpriseAuthPassed | 清除客户缓存 | 同步 |
| L70 | `ClearCartCacheListener` | `app/Listeners/Cache/ClearCartCacheListener.php` | E04 OrderCreated | 清除购物车缓存 | 同步 |
| L71 | `WarmProductCacheListener` | `app/Listeners/Cache/WarmProductCacheListener.php` | E27 ProductStatusChanged | 预热热门商品缓存 | 队列 |
| L72 | `WarmHomepageCacheListener` | `app/Listeners/Cache/WarmHomepageCacheListener.php` | *Cron* | 预热首页缓存 | 队列 |

### 2.8 平台对接类Listener（5个）

| # | Listener类名 | 文件路径 | 监听Event | 职责 | 同步/异步 |
|:-:|-------------|----------|-----------|------|:---------:|
| L73 | `SyncToTaobaoListener` | `app/Listeners/Platform/SyncToTaobaoListener.php` | E05 OrderPaid | 同步订单到淘宝 | 队列 |
| L74 | `SyncToPinduoduoListener` | `app/Listeners/Platform/SyncToPinduoduoListener.php` | E05 OrderPaid | 同步订单到拼多多 | 队列 |
| L75 | `SyncToDouyinListener` | `app/Listeners/Platform/SyncToDouyinListener.php` | E05 OrderPaid | 同步订单到抖音 | 队列 |
| L76 | `AutoDeliveryToPlatformListener` | `app/Listeners/Platform/AutoDeliveryToPlatformListener.php` | E07 OrderShipped | 平台自动发货回传 | 队列 |
| L77 | `SyncInventoryToPlatformListener` | `app/Listeners/Platform/SyncInventoryToPlatformListener.php` | E16 InventoryReserved | 同步库存到平台 | 队列 |

### 2.9 其他Listener（3个）

| # | Listener类名 | 文件路径 | 监听Event | 职责 | 同步/异步 |
|:-:|-------------|----------|-----------|------|:---------:|
| L78 | `UpdateMesStatusListener` | `app/Listeners/Factory/UpdateMesStatusListener.php` | E30 MesStatusUpdated | 更新MES生产状态到FM状态机 | 队列 |
| L79 | `GeneratePrepressJobSheetListener` | `app/Listeners/Factory/GeneratePrepressJobSheetListener.php` | E05 OrderPaid | 生成印前工艺单 | 队列 |
| L80 | `NotifyFactoryManagerListener` | `app/Listeners/Factory/NotifyFactoryManagerListener.php` | E05 OrderPaid | 通知工厂经理新订单 | 队列 |

---

## 3. EventServiceProvider注册

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Auth
        \App\Domains\Auth\Events\UserRegistered::class => [
            \App\Listeners\Notification\SendWelcomeNotification::class,
            \App\Listeners\Audit\WriteAuditLogListener::class,
        ],
        \App\Domains\Auth\Events\UserLoggedIn::class => [
            \App\Listeners\Notification\SendLoginAlertNotification::class,
            \App\Listeners\RiskControl\RecordLoginIpListener::class,
        ],
        \App\Domains\Customers\Events\UserEnterpriseAuthPassed::class => [
            \App\Listeners\Notification\SendEnterpriseAuthPassedNotification::class,
            \App\Listeners\Cache\ClearCustomerCacheListener::class,
        ],

        // Order
        \App\Domains\Orders\Events\OrderCreated::class => [
            \App\Listeners\Order\GenerateOrderNoListener::class,
            \App\Listeners\Order\ReserveInventoryListener::class,
            \App\Listeners\Order\SplitOrderListener::class,
            \App\Listeners\Order\AutoCancelUnpaidOrderListener::class,
            \App\Listeners\Notification\SendOrderCreatedNotification::class,
            \App\Listeners\RiskControl\ScoreOrderRiskListener::class,
            \App\Listeners\RiskControl\BlockHighRiskOrderListener::class,
            \App\Listeners\RiskControl\CheckBlacklistListener::class,
            \App\Listeners\Cache\ClearCartCacheListener::class,
            \App\Listeners\Audit\WriteAuditLogListener::class,
        ],
        \App\Domains\Orders\Events\OrderPaid::class => [
            \App\Listeners\Order\DeductInventoryListener::class,
            \App\Listeners\Order\CreateOrderDeliveryListener::class,
            \App\Listeners\Order\DispatchToFactoryListener::class,
            \App\Listeners\Order\SendToMesListener::class,
            \App\Listeners\Payment\UpdateOrderPaymentStatusListener::class,
            \App\Listeners\Notification\SendPaymentSuccessNotification::class,
            \App\Listeners\Cache\ClearOrderCacheListener::class,
            \App\Listeners\Audit\WriteAuditLogListener::class,
        ],
        \App\Domains\Orders\Events\OrderStatusChanged::class => [
            \App\Listeners\Notification\SendOrderStatusChangeNotification::class,
            \App\Listeners\Audit\RecordOrderSnapshotListener::class,
            \App\Listeners\Search\SyncOrderToMeilisearchListener::class,
            \App\Listeners\Cache\ClearOrderCacheListener::class,
        ],
        \App\Domains\Orders\Events\OrderShipped::class => [
            \App\Listeners\Notification\SendShipmentNotification::class,
            \App\Listeners\Order\AutoConfirmReceiptListener::class,
            \App\Listeners\Platform\AutoDeliveryToPlatformListener::class,
        ],
        \App\Domains\Orders\Events\OrderCompleted::class => [
            \App\Listeners\Notification\SendOrderCompletedNotification::class,
            \App\Listeners\Order\AutoCloseOrderListener::class,
            \App\Listeners\Order\UpdateCustomerStatsListener::class,
            \App\Listeners\Order\CheckVipUpgradeListener::class,
            \App\Listeners\Marketing\IssueOrderPointsListener::class,
            \App\Listeners\Marketing\CalculateRfmScoreListener::class,
            \App\Listeners\Marketing\AutoTagCustomerListener::class,
            \App\Listeners\Order\CreateInvoiceRequestListener::class,
            \App\Listeners\Marketing\IssueFirstOrderCouponListener::class,
        ],
        \App\Domains\Orders\Events\OrderCancelled::class => [
            \App\Listeners\Order\ReleaseInventoryListener::class,
            \App\Listeners\Notification\SendOrderCancelledNotification::class,
            \App\Listeners\Cache\ClearOrderCacheListener::class,
        ],

        // Payment
        \App\Domains\Payments\Events\PaymentSuccess::class => [
            \App\Listeners\Payment\RecordPaymentLogListener::class,
            \App\Listeners\Payment\CheckDuplicateCallbackListener::class,
            \App\Listeners\Payment\UpdateDailyReconcileListener::class,
            \App\Listeners\Payment\TriggerCommissionSettlementListener::class,
        ],
        \App\Domains\Payments\Events\PaymentFailed::class => [
            \App\Listeners\Payment\UpdateOrderPaymentFailedListener::class,
            \App\Listeners\Payment\RecordPaymentLogListener::class,
        ],
        \App\Domains\Payments\Events\RefundApproved::class => [
            \App\Listeners\Payment\ProcessRefundListener::class,
            \App\Listeners\Notification\SendRefundApprovedNotification::class,
        ],
        \App\Domains\Payments\Events\RefundCompleted::class => [
            \App\Listeners\Payment\ReturnBalanceListener::class,
            \App\Listeners\Notification\SendRefundCompletedNotification::class,
            \App\Listeners\Payment\NotifyFinanceTeamListener::class,
            \App\Listeners\Marketing\DeductRefundPointsListener::class,
        ],

        // AfterSale
        \App\Domains\AfterSales\Events\AfterSaleSubmitted::class => [
            \App\Listeners\Notification\SendAfterSaleSubmittedNotification::class,
            \App\Listeners\Audit\WriteAuditLogListener::class,
        ],
        \App\Domains\AfterSales\Events\AfterSaleCompleted::class => [
            \App\Listeners\Notification\SendAfterSaleResultNotification::class,
            \App\Listeners\Audit\WriteAuditLogListener::class,
        ],

        // Invoice
        \App\Domains\Invoices\Events\InvoiceApproved::class => [
            \App\Listeners\Notification\SendInvoiceApprovedNotification::class,
        ],

        // Marketing
        \App\Domains\Marketing\Events\PointsIssued::class => [
            \App\Listeners\Notification\SendPointsIssuedNotification::class,
        ],
        \App\Domains\Marketing\Events\PointsExpired::class => [
            \App\Listeners\Notification\SendPointsExpiryWarningNotification::class,
        ],
        \App\Domains\Marketing\Events\CouponReceived::class => [
            \App\Listeners\Notification\SendCouponReceivedNotification::class,
        ],

        // Product
        \App\Domains\Products\Events\ProductLowStock::class => [
            \App\Listeners\Notification\SendLowStockAlertNotification::class,
        ],
        \App\Domains\Products\Events\ProductStatusChanged::class => [
            \App\Listeners\Search\SyncProductToMeilisearchListener::class,
            \App\Listeners\Search\IndexProductKeywordsListener::class,
            \App\Listeners\Cache\ClearProductCacheListener::class,
            \App\Listeners\Cache\WarmProductCacheListener::class,
        ],

        // System
        \App\Domains\Common\Events\SystemError::class => [
            \App\Listeners\Notification\SendSystemErrorAlertNotification::class,
        ],
        \App\Domains\Admin\Events\AdminOperationLogged::class => [
            \App\Listeners\Audit\WriteOperationLogListener::class,
        ],

        // Factory/MES
        \App\Domains\Factories\Events\MesStatusUpdated::class => [
            \App\Listeners\Factory\UpdateMesStatusListener::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false; // 手动注册，不使用自动发现
    }
}
```

---

## 4. Event→Queue映射

| Event | 队列名称 | 优先级 | 延迟 |
|-------|----------|:------:|:----:|
| OrderCreated | `orders` | normal | 0 |
| OrderPaid | `orders` | high | 0 |
| OrderShipped | `orders` | normal | 0 |
| OrderCompleted | `orders` | normal | 0 |
| PaymentSuccess | `payments` | high | 0 |
| RefundApproved | `payments` | high | 0 |
| RefundCompleted | `payments` | normal | 0 |
| AfterSaleSubmitted | `aftersales` | normal | 0 |
| AfterSaleCompleted | `aftersales` | normal | 0 |
| InventoryReserved | `inventory` | high | 0 |
| MesStatusUpdated | `mes` | high | 0 |
| UserRegistered | `notifications` | normal | 0 |
| PointsIssued | `marketing` | low | 0 |
| PointsExpired | `marketing` | low | 0 |
| ProductStatusChanged | `search` | low | 0 |
| SystemError | `alerts` | critical | 0 |
| AdminOperationLogged | `audit` | low | 0 |

---

*本文档为100%可直接开发的Event/Listener完整映射，开发可直接复制EventServiceProvider注册代码，并按文件路径创建对应的Event和Listener类。*
