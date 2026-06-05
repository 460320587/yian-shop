<?php

declare(strict_types=1);

namespace App\Domains\Common\StateMachines;

use App\Domains\Common\StateMachines\Exceptions\InvalidTransitionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseStateMachine
{
    /**
     * 定义状态流转图
     * 格式: [from_status => [to_status1, to_status2, ...]]
     */
    abstract protected function transitions(): array;

    /**
     * 流转前校验/预处理
     */
    abstract protected function beforeTransition(Model $model, int $from, int $to, array $context): void;

    /**
     * 流转后处理（触发事件、记录日志等）
     */
    abstract protected function afterTransition(Model $model, int $from, int $to, array $context): void;

    /**
     * 检查是否允许从 $from 流转到 $to
     */
    public function canTransition(int $from, int $to): bool
    {
        $transitions = $this->transitions();
        return isset($transitions[$from]) && in_array($to, $transitions[$from], true);
    }

    /**
     * 获取从指定状态可流转的所有目标状态
     */
    public function getAvailableTransitions(int $from): array
    {
        return $this->transitions()[$from] ?? [];
    }

    /**
     * 执行状态流转
     *
     * @param Model $model 目标模型（必须有 status 字段）
     * @param int $to 目标状态
     * @param array $context 上下文数据（操作人、备注等）
     * @throws InvalidTransitionException
     */
    public function transition(Model $model, int $to, array $context = []): void
    {
        $from = (int) $model->getAttribute('status');

        if (!$this->canTransition($from, $to)) {
            throw new InvalidTransitionException(
                sprintf('状态不能从 [%d] 流转到 [%d]', $from, $to)
            );
        }

        DB::transaction(function () use ($model, $from, $to, $context) {
            $this->beforeTransition($model, $from, $to, $context);

            $model->setAttribute('status', $to);
            $model->save();

            $this->afterTransition($model, $from, $to, $context);
        });
    }
}
