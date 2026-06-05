<?php

declare(strict_types=1);

namespace App\Domains\AfterSale\StateMachines;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\Common\StateMachines\BaseStateMachine;
use Illuminate\Database\Eloquent\Model;

class AfterSaleStateMachine extends BaseStateMachine
{
    /**
     * 售后状态流转图
     * 1=待审核 2=审核通过 3=审核拒绝 4=处理中 5=已完成 6=已关闭
     */
    protected function transitions(): array
    {
        return [
            1 => [2, 3, 6], // 待审核 → 审核通过/审核拒绝/已关闭
            2 => [4, 6],    // 审核通过 → 处理中/已关闭
            3 => [],        // 审核拒绝（终态）
            4 => [5, 6],    // 处理中 → 已完成/已关闭
            5 => [],        // 已完成（终态）
            6 => [],        // 已关闭（终态）
        ];
    }

    protected function beforeTransition(Model $model, int $from, int $to, array $context): void
    {
        // 审核通过时写入 approved_amount
        if ($to === 2 && isset($context['approved_amount'])) {
            $model->setAttribute('approved_amount', $context['approved_amount']);
        }
    }

    protected function afterTransition(Model $model, int $from, int $to, array $context): void
    {
        // 完成时记录完成时间
        if ($to === 5) {
            $model->setAttribute('completed_at', now());
            $model->save();
        }

        // 触发售后事件
        if ($to === 1) {
            \App\Events\AfterSaleApplied::dispatch($model);
        }
    }
}
