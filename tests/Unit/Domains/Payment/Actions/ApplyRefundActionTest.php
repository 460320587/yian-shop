<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Actions;

use App\Domains\Order\Models\Order;
use App\Domains\Payment\Actions\ApplyRefundAction;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyRefundActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_refund_record(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $payment = Payment::factory()->create(['customer_id' => $customer->id, 'order_no' => $order->order_no]);

        $action = new ApplyRefundAction($customer->id, [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 5000,
            'reason' => '重复支付',
        ]);

        $refund = $action->handle();

        $this->assertInstanceOf(RefundRecord::class, $refund);
        $this->assertEquals(0, $refund->status);
        $this->assertEquals('original', $refund->refund_path);
        $this->assertStringStartsWith('R', $refund->refund_no);
    }
}
