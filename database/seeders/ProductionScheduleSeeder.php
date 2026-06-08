<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\ProductionSchedule;
use Illuminate\Database\Seeder;

class ProductionScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::whereIn('status', [12, 13, 15, 17, 20])->get(); // 已付款/生产中/生产完成/待发货/已发货

        if ($orders->isEmpty()) {
            return;
        }

        $schedules = [
            [
                'order_id' => $orders[0]->id,
                'schedule_date' => now()->addDays(1)->toDateString(),
                'process_name' => '印刷',
                'priority' => 2,
                'estimated_hours' => 4.5,
                'actual_hours' => null,
                'progress' => 0,
                'status' => 0,
            ],
            [
                'order_id' => $orders[0]->id,
                'schedule_date' => now()->addDays(2)->toDateString(),
                'process_name' => '覆膜',
                'priority' => 3,
                'estimated_hours' => 2.0,
                'actual_hours' => 1.5,
                'progress' => 50,
                'status' => 2,
            ],
            [
                'order_id' => $orders[1]->id ?? $orders[0]->id,
                'schedule_date' => now()->addDays(3)->toDateString(),
                'process_name' => '裁切',
                'priority' => 1,
                'estimated_hours' => 1.0,
                'actual_hours' => 1.0,
                'progress' => 100,
                'status' => 3,
            ],
        ];

        foreach ($schedules as $data) {
            ProductionSchedule::updateOrCreate(
                ['order_id' => $data['order_id'], 'process_name' => $data['process_name']],
                $data
            );
        }
    }
}
