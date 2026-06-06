<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Domains\Notification\Models\CustomerNotification;
use App\Domains\Order\Models\Order;
use App\Events\OrderCreated;
use App\Listeners\SendOrderCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendOrderCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_creates_notification(): void
    {
        $order = Order::factory()->create();
        $event = new OrderCreated($order);
        $listener = new SendOrderCreatedNotification();

        $listener->handle($event);

        $this->assertDatabaseHas('customer_notifications', [
            'customer_id' => $order->customer_id,
            'type' => 'order',
        ]);
    }
}
