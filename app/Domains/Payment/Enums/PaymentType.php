<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

use App\Domains\Common\Enums\BaseEnum;

enum PaymentType: int
{
    use BaseEnum;

    case Barcode = 1;   // 扫码支付
    case Wallet = 3;    // 余额支付
    case Offline = 8;   // 对公转账

    public function label(): string
    {
        return match ($this) {
            self::Barcode => '扫码支付',
            self::Wallet => '余额支付',
            self::Offline => '对公转账',
        };
    }
}
