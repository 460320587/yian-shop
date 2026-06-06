<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Services\WalletService;
use App\Infrastructure\Actions\BaseAction;

class ProcessRefundAction extends BaseAction
{
    public function __construct(
        private readonly RefundRecord $refund,
        private readonly WalletService $walletService,
    ) {
    }

    public function handle(): void
    {
        $this->transaction(function (): void {
            $this->walletService->credit(
                $this->refund->customer,
                $this->refund->amount,
                'refund',
                $this->refund->refund_no,
                '退款: ' . $this->refund->reason,
            );

            $this->refund->update([
                'status' => 4,
                'completed_at' => now(),
            ]);
        });
    }
}
