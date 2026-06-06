<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Domains\Payment\Models\Payment;
use App\Events\PaymentSuccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSuccessEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_can_be_instantiated(): void
    {
        $payment = Payment::factory()->make();
        $event = new PaymentSuccess($payment);

        $this->assertSame($payment, $event->payment);
    }

    public function test_event_is_dispatchable(): void
    {
        $reflection = new \ReflectionClass(PaymentSuccess::class);
        $this->assertTrue($reflection->hasMethod('dispatch'));
    }
}
