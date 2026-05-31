<?php

declare(strict_types=1);

namespace App\Domains\Order\Enums;

use App\Domains\Common\Enums\BaseEnum;

/**
 * FM 生产流程状态 (orders.status)
 * 注意：存在历史遗留跳号
 */
enum OrderStatus: int
{
    use BaseEnum;

    case PendingSubmit = 0;       // 待提交
    case Submitted = 1;           // 已提交
    case PendingPayment = 11;     // 待付款
    case Paid = 12;               // 已付款
    case InProduction = 13;       // 生产中
    case ProductionComplete = 15; // 生产完成
    case PendingDelivery = 17;    // 待发货
    case Shipped = 20;            // 已发货
    case PendingReceive = 54;     // 待收货
    case Received = 55;           // 已收货
    case Completed = 60;          // 已完成
    case Cancelled = 61;          // 已取消
    case Refunding = 62;          // 退款中
    case Refunded = 65;           // 已退款
    case Closed = 66;             // 已关闭
    case Exception = 67;          // 异常
    case PendingReview = 69;      // 待复核
    case Archived = 100;          // 已归档
    case Deleted = 101;           // 已删除
    case SystemError = 255;       // 系统错误

    public function label(): string
    {
        return match ($this) {
            self::PendingSubmit => '待提交',
            self::Submitted => '已提交',
            self::PendingPayment => '待付款',
            self::Paid => '已付款',
            self::InProduction => '生产中',
            self::ProductionComplete => '生产完成',
            self::PendingDelivery => '待发货',
            self::Shipped => '已发货',
            self::PendingReceive => '待收货',
            self::Received => '已收货',
            self::Completed => '已完成',
            self::Cancelled => '已取消',
            self::Refunding => '退款中',
            self::Refunded => '已退款',
            self::Closed => '已关闭',
            self::Exception => '异常',
            self::PendingReview => '待复核',
            self::Archived => '已归档',
            self::Deleted => '已删除',
            self::SystemError => '系统错误',
        };
    }

    public function canPay(): bool
    {
        return in_array($this, [self::PendingPayment, self::Submitted]);
    }

    public function canCancel(): bool
    {
        return in_array($this, [
            self::PendingSubmit,
            self::Submitted,
            self::PendingPayment,
            self::PendingReview,
        ]);
    }

    public function canAfterSale(): bool
    {
        return in_array($this, [
            self::ProductionComplete,
            self::PendingDelivery,
            self::Shipped,
            self::PendingReceive,
            self::Received,
            self::Completed,
        ]);
    }
}
