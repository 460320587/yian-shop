<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Actions;

use App\Domains\Order\Actions\CreateProductionScheduleAction;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\ProductionSchedule;
use App\Exceptions\BusinessException;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateProductionScheduleActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_schedule(): void
    {
        $order = Order::factory()->create();

        $action = new CreateProductionScheduleAction(
            $order,
            '2026-06-15',
            '印刷',
            2,
            4.5,
        );
        $schedule = $action->handle();

        $this->assertInstanceOf(ProductionSchedule::class, $schedule);
        $this->assertEquals($order->id, $schedule->order_id);
        $this->assertEquals('2026-06-15', $schedule->schedule_date->toDateString());
        $this->assertEquals('印刷', $schedule->process_name);
        $this->assertEquals(2, $schedule->priority);
        $this->assertEquals(4.5, $schedule->estimated_hours);
        $this->assertEquals(0, $schedule->status);
    }

    public function test_transitions_paid_order_to_in_production(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Paid->value]);

        (new CreateProductionScheduleAction($order, '2026-06-15', '印刷'))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::InProduction->value, $order->status);
    }

    public function test_does_not_transition_when_already_in_production(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::InProduction->value]);

        (new CreateProductionScheduleAction($order, '2026-06-15', '覆膜'))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::InProduction->value, $order->status);
    }

    public function test_does_not_transition_for_other_statuses(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Shipped->value]);

        (new CreateProductionScheduleAction($order, '2026-06-15', '装订'))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Shipped->value, $order->status);
    }

    public function test_records_status_log_on_transition(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Paid->value]);

        (new CreateProductionScheduleAction($order, '2026-06-15', '印刷'))->handle();

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Paid->value,
            'to_status' => OrderStatus::InProduction->value,
            'operator_type' => 'admin',
        ]);
    }
}
