<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

use App\Domains\Common\Enums\BaseEnum;

enum PaymentStatus: int
{
    use BaseEnum;

    case Pending = 1;   // 待支付
    case Success = 2;   // 支付成功
    case Failed = 3;    // 支付失败
    case Closed = 4;    // 已关闭

    public function label(): string
    {
        return match ($this) {
            self::Pending => '待支付',
            self::Success => '支付成功',
            self::Failed => '支付失败',
            self::Closed => '已关闭',
        };
    }
}
