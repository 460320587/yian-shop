<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Domains\Order\Models\Order;
use App\Events\OrderCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreatedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_can_be_instantiated(): void
    {
        $order = Order::factory()->make();
        $event = new OrderCreated($order);

        $this->assertSame($order, $event->order);
    }

    public function test_event_is_dispatchable(): void
    {
        $reflection = new \ReflectionClass(OrderCreated::class);
        $this->assertTrue($reflection->hasMethod('dispatch'));
    }
}
