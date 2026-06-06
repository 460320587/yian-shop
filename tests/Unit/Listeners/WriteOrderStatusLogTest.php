<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderStatusLog;
use App\Events\OrderStatusChanged;
use App\Listeners\WriteOrderStatusLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WriteOrderStatusLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_creates_status_log(): void
    {
        $order = Order::factory()->create(['status' => 10]);
        $event = new OrderStatusChanged($order, 10, 20);
        $listener = new WriteOrderStatusLog();

        $listener->handle($event);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => 10,
            'to_status' => 20,
            'operator_type' => 'system',
        ]);
    }
}
