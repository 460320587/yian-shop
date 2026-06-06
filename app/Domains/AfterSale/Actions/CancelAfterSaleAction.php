<?php

declare(strict_types=1);

namespace App\Domains\AfterSale\Actions;

use App\Domains\AfterSale\Models\AfterSale;
use App\Exceptions\BusinessException;
use App\Infrastructure\Actions\BaseAction;
use App\Support\ErrorCode;

class CancelAfterSaleAction extends BaseAction
{
    public function __construct(private readonly AfterSale $afterSale)
    {
    }

    public function handle(): void
    {
        if (! $this->afterSale->canCancel()) {
            throw new BusinessException(ErrorCode::ORDER_STATUS_INVALID, '当前状态不可取消');
        }

        $this->afterSale->stateMachine()->transition($this->afterSale, 6, [
            'operator_type' => 'customer',
            'operator_id' => null,
            'remark' => '客户取消售后申请',
        ]);
    }
}
