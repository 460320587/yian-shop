<?php

declare(strict_types=1);

namespace App\Domains\AfterSale\Enums;

use App\Domains\Common\Enums\BaseEnum;

enum AfterSaleType: int
{
    use BaseEnum;

    case ReturnRefund = 0;   // 退货退款
    case Reprint = 1;        // 补印
    case Discount = 2;       // 优惠货款
    case Other = 3;          // 其他

    public function label(): string
    {
        return match ($this) {
            self::ReturnRefund => '退货退款',
            self::Reprint => '补印',
            self::Discount => '优惠货款',
            self::Other => '其他',
        };
    }
}
