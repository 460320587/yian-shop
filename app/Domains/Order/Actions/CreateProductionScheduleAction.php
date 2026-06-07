<?php

declare(strict_types=1);

namespace App\Domains\Order\Actions;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\ProductionSchedule;
use App\Infrastructure\Actions\BaseAction;

class CreateProductionScheduleAction extends BaseAction
{
    public function __construct(
        private readonly Order $order,
        private readonly string $scheduleDate,
        private readonly string $processName,
        private readonly int $priority = 3,
        private readonly ?float $estimatedHours = null,
    ) {
    }

    public function handle(): ProductionSchedule
    {
        return $this->transaction(function (): ProductionSchedule {
            $schedule = ProductionSchedule::create([
                'order_id' => $this->order->id,
                'schedule_date' => $this->scheduleDate,
                'process_name' => $this->processName,
                'priority' => $this->priority,
                'estimated_hours' => $this->estimatedHours,
                'status' => 0,
                'progress' => 0,
            ]);

            $this->maybeTransitionToInProduction();

            return $schedule;
        });
    }

    private function maybeTransitionToInProduction(): void
    {
        $currentStatus = (int) $this->order->status;

        if ($currentStatus === OrderStatus::Paid->value) {
            $this->order->stateMachine()->transition($this->order, OrderStatus::InProduction->value, [
                'operator_type' => 'admin',
                'operator_id' => auth('admin')->id() ?? 0,
                'remark' => '创建生产排期: ' . $this->processName,
            ]);
        }
    }
}
