<?php

declare(strict_types=1);

namespace App\Domains\Coupon\Services;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Coupon\Models\Coupon;

class CouponDiscountCalculator
{
    /**
     * 计算优惠券抵扣金额
     *
     * @param Coupon $coupon 优惠券模板
     * @param int $orderAmount 订单商品总金额（分）
     * @return int 抵扣金额（分）
     */
    public static function calculate(Coupon $coupon, int $orderAmount): int
    {
        // 不满足最低消费门槛
        if ($coupon->min_amount->amount > 0 && $orderAmount < $coupon->min_amount->amount) {
            return 0;
        }

        $discount = match ($coupon->type) {
            // 满减券：直接减 value
            1 => $coupon->value,
            // 折扣券：total * (1 - value)，value 存储的是折扣比例如 850 表示 85折
            2 => (int) round($orderAmount * (1 - $coupon->value / 1000)),
            // 直减券：直接减 value
            3 => $coupon->value,
            default => 0,
        };

        // 折扣券上限控制
        if ($coupon->type === 2 && $coupon->max_discount->amount > 0) {
            $discount = min($discount, $coupon->max_discount->amount);
        }

        // 抵扣金额不能超过订单金额
        return min($discount, $orderAmount);
    }
}
