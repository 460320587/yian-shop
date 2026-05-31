<?php

declare(strict_types=1);

namespace App\Domains\Enterprise\Enums;

use App\Domains\Common\Enums\BaseEnum;

enum AuthStatus: int
{
    use BaseEnum;

    case Unverified = 0;       // 未认证
    case Pending = 1;          // 待审核
    case Approved = 2;         // 已认证
    case Rejected = 4;         // 已驳回
    case PreApproved = 20;     // 代认证通过

    public function label(): string
    {
        return match ($this) {
            self::Unverified => '未认证',
            self::Pending => '待审核',
            self::Approved => '已认证',
            self::Rejected => '已驳回',
            self::PreApproved => '代认证通过',
        };
    }

    public function isApproved(): bool
    {
        return in_array($this, [self::Approved, self::PreApproved]);
    }
}
