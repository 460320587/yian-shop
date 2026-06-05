<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\ProductionSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_schedule(): void
    {
        $schedule = ProductionSchedule::factory()->create();
        $this->assertDatabaseHas('production_schedules', ['id' => $schedule->id]);
    }

    public function test_schedule_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $schedule = ProductionSchedule::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $schedule->order);
        $this->assertEquals($order->id, $schedule->order->id);
    }

    public function test_casts_are_correct(): void
    {
        $schedule = new ProductionSchedule();
        $casts = $schedule->getCasts();

        $this->assertArrayHasKey('status', $casts);
        $this->assertArrayHasKey('priority', $casts);
        $this->assertArrayHasKey('progress', $casts);
        $this->assertArrayHasKey('estimated_hours', $casts);
    }
}
