<?php

declare(strict_types=1);

namespace App\Domains\Order\Actions;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\ProductionSchedule;
use App\Infrastructure\Actions\BaseAction;

class UpdateProductionScheduleAction extends BaseAction
{
    public function __construct(
        private readonly ProductionSchedule $schedule,
        private readonly array $data,
    ) {
    }

    public function handle(): void
    {
        $this->transaction(function (): void {
            $updateData = array_intersect_key($this->data, array_flip([
                'schedule_date',
                'start_time',
                'end_time',
                'process_name',
                'priority',
                'estimated_hours',
                'actual_hours',
                'progress',
                'status',
                'delay_reason',
            ]));

            if (isset($updateData['progress'])) {
                $updateData['progress'] = max(0, min(100, (int) $updateData['progress']));
            }

            $this->schedule->update($updateData);

            $this->maybeTransitionToProductionComplete();
        });
    }

    private function maybeTransitionToProductionComplete(): void
    {
        if (! isset($this->data['progress']) || (int) $this->data['progress'] !== 100) {
            return;
        }

        $order = $this->schedule->order;
        if (! $order) {
            return;
        }

        if ((int) $order->status !== OrderStatus::InProduction->value) {
            return;
        }

        $this->schedule->update(['status' => 3]); // 已完成

        $order->stateMachine()->transition($order, OrderStatus::ProductionComplete->value, [
            'operator_type' => 'admin',
            'operator_id' => auth('admin')->id() ?? 0,
            'remark' => '生产排期完成: ' . $this->schedule->process_name,
        ]);
    }
}
