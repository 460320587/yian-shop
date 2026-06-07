<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_wechat_pay_callback_can_confirm_payment(): void
    {
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

        $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => 5000,
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 'SUCCESS']);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Success->value,
            'transaction_no' => 'WX123456',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Paid->value,
        ]);
    }

    public function test_wechat_pay_callback_rejects_invalid_amount(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'gateway' => 'wechat',
        ]);

        $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => 4999,
        ]);

        $response->assertStatus(400);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_wechat_pay_callback_ignores_non_success_state(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending->value,
            'gateway' => 'wechat',
        ]);

        $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'NOTPAY',
            'total_fee' => $payment->amount->amount,
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 'SUCCESS']);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_alipay_callback_can_confirm_payment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $payment = Payment::factory()->create([
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
            'gateway' => 'alipay',
        ]);

        $response = $this->postJson('/api/v1/webhooks/alipay', [
            'out_trade_no' => $payment->payment_no,
            'trade_no' => 'ALI123456',
            'trade_status' => 'TRADE_SUCCESS',
            'total_amount' => '50.00',
        ]);

        $response->assertStatus(200)
            ->assertSeeText('success');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Success->value,
            'transaction_no' => 'ALI123456',
        ]);
    }

    public function test_alipay_callback_ignores_non_success_status(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending->value,
            'gateway' => 'alipay',
        ]);

        $response = $this->postJson('/api/v1/webhooks/alipay', [
            'out_trade_no' => $payment->payment_no,
            'trade_no' => 'ALI123456',
            'trade_status' => 'WAIT_BUYER_PAY',
            'total_amount' => '50.00',
        ]);

        $response->assertStatus(200)
            ->assertSeeText('success');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_callback_creates_payment_log(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending->value,
            'gateway' => 'wechat',
        ]);

        $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => $payment->amount->amount,
        ]);

        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'callback',
            'from_status' => PaymentStatus::Pending->value,
            'to_status' => PaymentStatus::Success->value,
        ]);
    }

    public function test_callback_is_idempotent(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Success->value,
            'gateway' => 'wechat',
            'paid_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => $payment->amount->amount,
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 'SUCCESS']);
    }

    public function test_wechat_pay_rejects_invalid_signature_in_strict_mode(): void
    {
        config()->set('payment.webhook_verify_mode', 'strict');

        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending->value,
            'gateway' => 'wechat',
        ]);

        $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => $payment->amount->amount,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_wechat_pay_accepts_valid_signature_in_strict_mode(): void
    {
        config()->set('payment.webhook_verify_mode', 'strict');

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

        $response = $this->postJson('/api/v1/webhooks/wechat-pay', [
            'out_trade_no' => $payment->payment_no,
            'transaction_id' => 'WX123456',
            'trade_state' => 'SUCCESS',
            'total_fee' => 5000,
        ], [
            'X-Mock-Signature' => 'test',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 'SUCCESS']);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Success->value,
        ]);
    }

    public function test_alipay_rejects_invalid_signature_in_strict_mode(): void
    {
        config()->set('payment.webhook_verify_mode', 'strict');

        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending->value,
            'gateway' => 'alipay',
        ]);

        $response = $this->postJson('/api/v1/webhooks/alipay', [
            'out_trade_no' => $payment->payment_no,
            'trade_no' => 'ALI123456',
            'trade_status' => 'TRADE_SUCCESS',
            'total_amount' => '50.00',
        ]);

        $response->assertStatus(422);
    }
}
