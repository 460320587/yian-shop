<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Services;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
use App\Events\PaymentSuccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService();
    }

    public function test_confirm_dispatches_payment_success_event(): void
    {
        Event::fake([PaymentSuccess::class]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $payment = Payment::factory()->create([
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'gateway' => 'wechat',
        ]);

        $this->service->confirm($payment, 'WX123456');

        Event::assertDispatched(PaymentSuccess::class, function (PaymentSuccess $event) use ($payment) {
            return $event->payment->id === $payment->id;
        });
    }

    public function test_confirm_is_idempotent_and_does_not_dispatch_twice(): void
    {
        Event::fake([PaymentSuccess::class]);

        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Success->value,
            'amount' => 5000,
        ]);

        $this->service->confirm($payment, 'WX123456');

        Event::assertNotDispatched(PaymentSuccess::class);
    }

    public function test_confirm_records_wallet_transaction_for_wallet_gateway(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'gateway' => 'wallet',
            'order_no' => 'Y202601010001',
        ]);

        $this->service->confirm($payment, 'TXN123');

        $this->assertDatabaseHas('wallet_transactions', [
            'payment_no' => $payment->payment_no,
            'type' => 2, // consume
            'amount' => 5000,
        ]);
    }
}
