<?php

declare(strict_types=1);

namespace App\Domains\Order\StateMachines;

use App\Domains\Common\StateMachines\BaseStateMachine;
use App\Domains\Order\Models\OrderStatusLog;
use Illuminate\Database\Eloquent\Model;

class OrderStateMachine extends BaseStateMachine
{
    /**
     * 订单状态流转图
     */
    protected function transitions(): array
    {
        return [
            // 待提交
            0 => [1, 61],
            // 已提交
            1 => [11, 61],
            // 待付款
            11 => [12, 61],
            // 已付款
            12 => [13, 62],
            // 生产中
            13 => [15],
            // 生产完成
            15 => [17],
            // 待发货
            17 => [20],
            // 已发货
            20 => [54, 62],
            // 待收货
            54 => [55],
            // 已收货
            55 => [60, 62],
            // 已完成
            60 => [62, 100],
            // 已取消
            61 => [],
            // 退款中
            62 => [65, 66],
            // 已退款
            65 => [],
            // 已关闭
            66 => [],
            // 异常
            67 => [],
            // 待复核
            69 => [61],
            // 已归档
            100 => [],
            // 已删除
            101 => [],
            // 系统错误
            255 => [],
        ];
    }

    protected function beforeTransition(Model $model, int $from, int $to, array $context): void
    {
        // 可扩展：库存校验、权限校验等
    }

    protected function afterTransition(Model $model, int $from, int $to, array $context): void
    {
        $this->recordStatusLog($model, $from, $to, $context);

        // 触发对应事件
        match ($to) {
            12 => \App\Events\OrderCreated::dispatchIf(false, $model), // 示例占位
            20 => \App\Events\OrderDelivered::dispatch($model, $context['tracking_no'] ?? ''),
            default => null,
        };
    }

    private function recordStatusLog(Model $model, int $from, int $to, array $context): void
    {
        OrderStatusLog::create([
            'order_id' => $model->id,
            'from_status' => $from,
            'to_status' => $to,
            'operator_type' => $context['operator_type'] ?? 'system',
            'operator_id' => ($context['operator_id'] ?? 0) > 0 ? $context['operator_id'] : null,
            'remark' => $context['remark'] ?? null,
        ]);
    }
}
