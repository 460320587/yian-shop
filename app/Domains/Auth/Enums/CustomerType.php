<?php

declare(strict_types=1);

namespace App\Domains\Auth\Enums;

use App\Domains\Common\Enums\BaseEnum;

enum CustomerType: int
{
    use BaseEnum;

    case Personal = 3;      // 个人用户
    case Enterprise = 4;    // 企业用户
    case Distributor = 5;   // 经销商
    case Factory = 6;       // 工厂
    case Store = 7;         // 门店
    case Platform = 8;      // 平台

    public function label(): string
    {
        return match ($this) {
            self::Personal => '个人用户',
            self::Enterprise => '企业用户',
            self::Distributor => '经销商',
            self::Factory => '工厂',
            self::Store => '门店',
            self::Platform => '平台',
        };
    }
}
