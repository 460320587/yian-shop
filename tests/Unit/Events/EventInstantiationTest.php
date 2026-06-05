<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\Payment;
use App\Events\AfterSaleApplied;
use App\Events\OrderCreated;
use App\Events\OrderDelivered;
use App\Events\PaymentSuccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventInstantiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_created_event_can_be_instantiated(): void
    {
        $order = Order::factory()->create();
        $event = new OrderCreated($order);

        $this->assertInstanceOf(Order::class, $event->order);
        $this->assertEquals($order->id, $event->order->id);
    }

    public function test_payment_success_event_can_be_instantiated(): void
    {
        $payment = Payment::factory()->create();
        $event = new PaymentSuccess($payment);

        $this->assertInstanceOf(Payment::class, $event->payment);
        $this->assertEquals($payment->id, $event->payment->id);
    }

    public function test_order_delivered_event_can_be_instantiated(): void
    {
        $order = Order::factory()->create();
        $event = new OrderDelivered($order, 'SF123456789');

        $this->assertInstanceOf(Order::class, $event->order);
        $this->assertEquals('SF123456789', $event->trackingNo);
    }

    public function test_after_sale_applied_event_can_be_instantiated(): void
    {
        $afterSale = AfterSale::factory()->create();
        $event = new AfterSaleApplied($afterSale);

        $this->assertInstanceOf(AfterSale::class, $event->afterSale);
        $this->assertEquals($afterSale->id, $event->afterSale->id);
    }

    public function test_events_are_dispatchable(): void
    {
        $this->assertTrue(method_exists(OrderCreated::class, 'dispatch'));
        $this->assertTrue(method_exists(PaymentSuccess::class, 'dispatch'));
        $this->assertTrue(method_exists(OrderDelivered::class, 'dispatch'));
        $this->assertTrue(method_exists(AfterSaleApplied::class, 'dispatch'));
    }
}
