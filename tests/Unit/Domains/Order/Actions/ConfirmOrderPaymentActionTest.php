<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Actions;

use App\Domains\Admin\Models\Admin;
use App\Domains\Order\Actions\ConfirmOrderPaymentAction;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use App\Exceptions\BusinessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmOrderPaymentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirms_payment(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        (new ConfirmOrderPaymentAction($order, $admin->id))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid->value, $order->status);
    }

    public function test_creates_status_log(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        (new ConfirmOrderPaymentAction($order, $admin->id))->handle();

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::PendingPayment->value,
            'to_status' => OrderStatus::Paid->value,
            'operator_type' => 'admin',
            'operator_id' => $admin->id,
        ]);
    }

    public function test_throws_when_order_already_paid(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
        ]);

        $this->expectException(BusinessException::class);
        (new ConfirmOrderPaymentAction($order, $admin->id))->handle();
    }
}
