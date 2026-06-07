<?php

declare(strict_types=1);

namespace App\Domains\Payment\StateMachines;

use App\Domains\Common\StateMachines\BaseStateMachine;
use Illuminate\Database\Eloquent\Model;

class RefundStateMachine extends BaseStateMachine
{
    /**
     * 退款状态流转图
     * 0=待处理 1=审核通过 2=审核拒绝 3=处理中 4=已完成
     */
    protected function transitions(): array
    {
        return [
            0 => [1, 2], // 待处理 → 审核通过/审核拒绝
            1 => [3],    // 审核通过 → 处理中
            2 => [],     // 审核拒绝（终态）
            3 => [4],    // 处理中 → 已完成
            4 => [],     // 已完成（终态）
        ];
    }

    protected function beforeTransition(Model $model, int $from, int $to, array $context): void
    {
        // 审核通过或拒绝时记录审核人
        if (in_array($to, [1, 2], true) && isset($context['approved_by'])) {
            $model->setAttribute('approved_by', $context['approved_by']);
            $model->setAttribute('approved_at', now());
        }
    }

    protected function afterTransition(Model $model, int $from, int $to, array $context): void
    {
        // 完成时记录完成时间
        if ($to === 4) {
            $model->setAttribute('completed_at', now());
            $model->save();
        }
    }
}
