<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Domains\Payment\Models\Payment;
use App\Events\PaymentSuccess;
use App\Listeners\AwardPointsOnPayment;
use App\Listeners\WritePaymentSuccessLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSuccessListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_payment_success_log(): void
    {
        $payment = Payment::factory()->create(['amount' => 10000]);
        $event = new PaymentSuccess($payment);
        $listener = new WritePaymentSuccessLog();

        $listener->handle($event);

        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $payment->customer_id,
            'action' => 'payment_success',
            'model_type' => 'payment',
            'model_id' => $payment->id,
        ]);
    }

    public function test_award_points_on_payment(): void
    {
        $payment = Payment::factory()->create(['amount' => 15000]);
        $event = new PaymentSuccess($payment);
        $listener = new AwardPointsOnPayment();

        $listener->handle($event);

        $this->assertDatabaseHas('customer_points_logs', [
            'customer_id' => $payment->customer_id,
            'type' => 1,
            'points' => 150,
            'order_no' => $payment->order_no,
        ]);
    }

    public function test_award_points_zero_for_small_amount(): void
    {
        $payment = Payment::factory()->create(['amount' => 50]);
        $event = new PaymentSuccess($payment);
        $listener = new AwardPointsOnPayment();

        $listener->handle($event);

        $this->assertDatabaseMissing('customer_points_logs', [
            'order_no' => $payment->order_no,
        ]);
    }
}
