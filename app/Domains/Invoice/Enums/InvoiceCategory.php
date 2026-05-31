<?php

declare(strict_types=1);

namespace App\Domains\Invoice\Enums;

use App\Domains\Common\Enums\BaseEnum;

enum InvoiceCategory: int
{
    use BaseEnum;

    case Normal = 0;     // 普通发票
    case Special = 1;    // 专用发票
    case Electronic = 2; // 电子发票
    case Red = 3;        // 红冲发票

    public function label(): string
    {
        return match ($this) {
            self::Normal => '普通发票',
            self::Special => '专用发票',
            self::Electronic => '电子发票',
            self::Red => '红冲发票',
        };
    }
}
