<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Actions;

use App\Domains\Coupon\Models\Coupon;
use App\Domains\Coupon\Models\CustomerCoupon;
use App\Domains\Order\Actions\CancelOrderAction;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use App\Exceptions\BusinessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_pending_payment_order(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        (new CancelOrderAction($order))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Cancelled->value, $order->status);
    }

    public function test_cancel_creates_status_log(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        (new CancelOrderAction($order))->handle();

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::PendingPayment->value,
            'to_status' => OrderStatus::Cancelled->value,
        ]);
    }

    public function test_cancel_restores_coupon(): void
    {
        $customer = Customer::factory()->create();
        $coupon = Coupon::factory()->create(['used_count' => 1]);
        $customerCoupon = CustomerCoupon::factory()->create([
            'customer_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'status' => 2,
            'used_at' => now(),
        ]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'customer_coupon_id' => $customerCoupon->id,
        ]);

        (new CancelOrderAction($order))->handle();

        $customerCoupon->refresh();
        $this->assertEquals(1, $customerCoupon->status);
        $this->assertNull($customerCoupon->used_at);

        $coupon->refresh();
        $this->assertEquals(0, $coupon->used_count);
    }

    public function test_cancel_paid_order_throws_exception(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
        ]);

        $this->expectException(BusinessException::class);
        (new CancelOrderAction($order))->handle();
    }
}
