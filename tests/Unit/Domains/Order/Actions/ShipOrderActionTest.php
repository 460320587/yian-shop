<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Actions;

use App\Domains\Admin\Models\Admin;
use App\Domains\Logistics\Models\OrderDelivery;
use App\Domains\Order\Actions\ShipOrderAction;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use App\Exceptions\BusinessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ships_order(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
        ]);

        (new ShipOrderAction($order, $admin->id, '顺丰速运'))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Shipped->value, $order->status);
        $this->assertEquals('顺丰速运', $order->express_company);
    }

    public function test_creates_delivery_with_tracking_no(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
        ]);

        (new ShipOrderAction($order, $admin->id, '顺丰速运', 'SF123456'))->handle();

        $this->assertDatabaseHas('order_deliveries', [
            'order_id' => $order->id,
            'carrier_name' => '顺丰速运',
            'tracking_no' => 'SF123456',
        ]);
    }

    public function test_does_not_create_delivery_without_tracking_no(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
        ]);

        (new ShipOrderAction($order, $admin->id, '顺丰速运'))->handle();

        $this->assertDatabaseMissing('order_deliveries', [
            'order_id' => $order->id,
        ]);
    }

    public function test_ships_order_from_pending_delivery(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingDelivery->value,
        ]);

        (new ShipOrderAction($order, $admin->id, '顺丰速运'))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Shipped->value, $order->status);
    }

    public function test_throws_when_order_not_paid_or_pending_delivery(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $this->expectException(BusinessException::class);
        (new ShipOrderAction($order, $admin->id, '顺丰速运'))->handle();
    }
}
