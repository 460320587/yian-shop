<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Actions;

use App\Domains\Order\Actions\UpdateProductionScheduleAction;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\ProductionSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProductionScheduleActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_schedule(): void
    {
        $schedule = ProductionSchedule::factory()->create([
            'process_name' => '印刷',
            'priority' => 3,
        ]);

        (new UpdateProductionScheduleAction($schedule, [
            'process_name' => '裁切',
            'priority' => 1,
            'estimated_hours' => 2.0,
        ]))->handle();

        $schedule->refresh();
        $this->assertEquals('裁切', $schedule->process_name);
        $this->assertEquals(1, $schedule->priority);
        $this->assertEquals(2.0, $schedule->estimated_hours);
    }

    public function test_updates_progress(): void
    {
        $schedule = ProductionSchedule::factory()->create(['progress' => 20]);

        (new UpdateProductionScheduleAction($schedule, ['progress' => 60]))->handle();

        $schedule->refresh();
        $this->assertEquals(60, $schedule->progress);
    }

    public function test_transitions_to_production_complete_at_100_progress(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::InProduction->value]);
        $schedule = ProductionSchedule::factory()->create([
            'order_id' => $order->id,
            'progress' => 80,
            'status' => 2,
        ]);

        (new UpdateProductionScheduleAction($schedule, ['progress' => 100]))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::ProductionComplete->value, $order->status);
        $this->assertEquals(3, $schedule->fresh()->status); // 已完成
    }

    public function test_does_not_transition_when_not_at_100(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::InProduction->value]);
        $schedule = ProductionSchedule::factory()->create([
            'order_id' => $order->id,
            'progress' => 50,
        ]);

        (new UpdateProductionScheduleAction($schedule, ['progress' => 99]))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::InProduction->value, $order->status);
    }

    public function test_does_not_transition_when_order_not_in_production(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Paid->value]);
        $schedule = ProductionSchedule::factory()->create([
            'order_id' => $order->id,
            'progress' => 80,
            'status' => 2,
        ]);

        (new UpdateProductionScheduleAction($schedule, ['progress' => 100]))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid->value, $order->status);
    }

    public function test_records_status_log_on_completion_transition(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::InProduction->value]);
        $schedule = ProductionSchedule::factory()->create([
            'order_id' => $order->id,
            'progress' => 90,
            'status' => 2,
        ]);

        (new UpdateProductionScheduleAction($schedule, ['progress' => 100]))->handle();

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::InProduction->value,
            'to_status' => OrderStatus::ProductionComplete->value,
        ]);
    }
}
