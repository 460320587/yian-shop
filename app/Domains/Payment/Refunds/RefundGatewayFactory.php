<?php

declare(strict_types=1);

namespace App\Domains\Payment\Refunds;

use App\Domains\Payment\Services\WalletService;
use InvalidArgumentException;

/**
 * 退款网关工厂
 *
 * 根据退款路径创建对应的网关实例。
 * 当 payment.use_real_gateway 为 true 时，original 路径使用真实网关。
 */
class RefundGatewayFactory
{
    public static function make(string $path): RefundGatewayInterface
    {
        $useReal = config('payment.use_real_gateway', false);

        return match ($path) {
            'original' => $useReal ? new OriginalRefundGateway() : new MockRefundGateway(),
            'wallet' => new WalletRefundGateway(app(WalletService::class)),
            default => throw new InvalidArgumentException("Unsupported refund path: {$path}"),
        };
    }
}
