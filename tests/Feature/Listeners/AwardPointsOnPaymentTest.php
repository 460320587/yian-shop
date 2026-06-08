<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Domains\Payment\Models\Payment;
use App\Domains\Points\Models\CustomerPointsLog;
use App\Domains\User\Models\Customer;
use App\Events\PaymentSuccess;
use App\Listeners\AwardPointsOnPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwardPointsOnPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_awards_points_and_updates_customer_balance(): void
    {
        $customer = Customer::factory()->create(['points' => 100, 'grow_value' => 500]);
        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 25000, // 250 yuan
            'status' => 1,
        ]);

        $listener = new AwardPointsOnPayment();
        $listener->handle(new PaymentSuccess($payment));

        $customer->refresh();
        $this->assertEquals(350, $customer->points);     // 100 + 250
        $this->assertEquals(750, $customer->grow_value); // 500 + 250

        $this->assertDatabaseHas('customer_points_logs', [
            'customer_id' => $customer->id,
            'type' => 1,
            'points' => 250,
            'balance_before' => 100,
            'balance_after' => 350,
            'order_no' => $payment->order_no,
        ]);
    }

    public function test_it_awards_points_from_zero(): void
    {
        $customer = Customer::factory()->create(['points' => 0, 'grow_value' => 0]);
        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 10000, // 100 yuan
            'status' => 1,
        ]);

        $listener = new AwardPointsOnPayment();
        $listener->handle(new PaymentSuccess($payment));

        $customer->refresh();
        $this->assertEquals(100, $customer->points);
        $this->assertEquals(100, $customer->grow_value);

        $this->assertDatabaseHas('customer_points_logs', [
            'customer_id' => $customer->id,
            'balance_before' => 0,
            'balance_after' => 100,
        ]);
    }

    public function test_it_skips_zero_amount_payment(): void
    {
        $customer = Customer::factory()->create(['points' => 100]);
        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 0,
            'status' => 1,
        ]);

        $listener = new AwardPointsOnPayment();
        $listener->handle(new PaymentSuccess($payment));

        $customer->refresh();
        $this->assertEquals(100, $customer->points);
        $this->assertDatabaseCount('customer_points_logs', 0);
    }
}
