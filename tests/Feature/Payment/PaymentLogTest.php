<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentLogTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(?array $attrs = []): Customer
    {
        $customer = Customer::factory()->create($attrs);
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_create_payment_writes_log(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);

        $this->postJson('/api/v1/payments/create', [
            'order_no' => $order->order_no,
            'gateway' => 'wechat',
        ]);

        $payment = Payment::where('order_no', $order->order_no)->first();
        $this->assertNotNull($payment);

        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'create',
            'to_status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_mock_callback_success_writes_log(): void
    {
        $customer = $this->authCustomer();
        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'status' => PaymentStatus::Pending->value,
        ]);

        $this->postJson('/api/v1/payments/' . $payment->id . '/mock-callback', [
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'callback',
            'from_status' => PaymentStatus::Pending->value,
            'to_status' => PaymentStatus::Success->value,
        ]);
    }

    public function test_mock_callback_failure_writes_log(): void
    {
        $customer = $this->authCustomer();
        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'status' => PaymentStatus::Pending->value,
        ]);

        $this->postJson('/api/v1/payments/' . $payment->id . '/mock-callback', [
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'callback',
            'from_status' => PaymentStatus::Pending->value,
            'to_status' => PaymentStatus::Failed->value,
        ]);
    }

    public function test_wallet_payment_writes_log(): void
    {
        $customer = $this->authCustomer(['balance' => 10000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);

        $this->postJson('/api/v1/payments/create', [
            'order_no' => $order->order_no,
            'gateway' => 'wallet',
        ]);

        $payment = Payment::where('order_no', $order->order_no)->first();

        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'create',
            'to_status' => PaymentStatus::Success->value,
        ]);
    }
}
