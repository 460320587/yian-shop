<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\User\Models\Customer;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(?array $attrs = []): Customer
    {
        $customer = Customer::factory()->create($attrs);
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_create_wallet_payment(): void
    {
        $customer = $this->authCustomer(['balance' => 10000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);

        $response = $this->postJson('/api/v1/payments/create', [
            'order_no' => $order->order_no,
            'gateway' => 'wallet',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', PaymentStatus::Success->value)
            ->assertJsonPath('data.gateway', 'wallet');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Paid->value,
        ]);

        $customer->refresh();
        $this->assertEquals(50, $customer->balance->toYuan());
    }

    public function test_user_can_create_mock_gateway_payment(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);

        $response = $this->postJson('/api/v1/payments/create', [
            'order_no' => $order->order_no,
            'gateway' => 'wechat',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', PaymentStatus::Pending->value)
            ->assertJsonStructure([
                'data' => [
                    'payment_no',
                    'amount',
                    'gateway',
                    'credential' => ['type', 'qrcode_url'],
                    'expire_at',
                ],
            ]);
    }

    public function test_create_payment_fails_for_nonexistent_order(): void
    {
        $this->authCustomer();

        $response = $this->postJson('/api/v1/payments/create', [
            'order_no' => 'Y99999999000000',
            'gateway' => 'wallet',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', ErrorCode::ORDER_NOT_FOUND->value);
    }

    public function test_create_payment_fails_for_others_order(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $otherCustomer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $response = $this->postJson('/api/v1/payments/create', [
            'order_no' => $order->order_no,
            'gateway' => 'wallet',
        ]);

        $response->assertStatus(404);
    }

    public function test_create_payment_fails_for_paid_order(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
        ]);

        $response = $this->postJson('/api/v1/payments/create', [
            'order_no' => $order->order_no,
            'gateway' => 'wallet',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::ORDER_STATUS_INVALID->value);
    }

    public function test_user_can_query_payment_status(): void
    {
        $customer = $this->authCustomer();
        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'status' => PaymentStatus::Pending->value,
        ]);

        $response = $this->getJson('/api/v1/payments/' . $payment->id . '/status');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.payment_no', $payment->payment_no)
            ->assertJsonPath('data.status', PaymentStatus::Pending->value);
    }

    public function test_user_cannot_query_others_payment(): void
    {
        $this->authCustomer();
        $otherCustomer = Customer::factory()->create();
        $payment = Payment::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $response = $this->getJson('/api/v1/payments/' . $payment->id . '/status');
        $response->assertStatus(403);
    }

    public function test_user_can_recharge_wallet(): void
    {
        $customer = $this->authCustomer(['balance' => 1000]);

        $response = $this->postJson('/api/v1/payments/wallet/recharge', [
            'amount' => 100,
            'gateway' => 'alipay',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0);

        $customer->refresh();
        $this->assertEquals(110, $customer->balance->toYuan());
    }

    public function test_wallet_payment_fails_with_insufficient_balance(): void
    {
        $customer = $this->authCustomer(['balance' => 1000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);

        $response = $this->postJson('/api/v1/payments/create', [
            'order_no' => $order->order_no,
            'gateway' => 'wallet',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::INSUFFICIENT_BALANCE->value);
    }

    public function test_mock_callback_can_mark_payment_success(): void
    {
        $customer = $this->authCustomer();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'order_no' => $order->order_no,
            'status' => PaymentStatus::Pending->value,
            'amount' => 5000,
        ]);

        $response = $this->postJson('/api/v1/payments/' . $payment->id . '/mock-callback', [
            'status' => 'success',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Success->value,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Paid->value,
        ]);
    }

    public function test_mock_callback_can_mark_payment_failed(): void
    {
        $customer = $this->authCustomer();
        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'status' => PaymentStatus::Pending->value,
        ]);

        $response = $this->postJson('/api/v1/payments/' . $payment->id . '/mock-callback', [
            'status' => 'failed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Failed->value,
        ]);
    }

    public function test_user_can_withdraw_wallet_balance(): void
    {
        $customer = $this->authCustomer(['balance' => 10000]);

        $response = $this->postJson('/api/v1/payments/wallet/withdraw', [
            'amount' => 50,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', PaymentStatus::Success->value)
            ->assertJsonPath('data.amount', 50);

        $customer->refresh();
        $this->assertEquals(50, $customer->balance->toYuan());

        $this->assertDatabaseHas('payments', [
            'customer_id' => $customer->id,
            'gateway' => 'withdraw',
            'amount' => 5000,
            'status' => PaymentStatus::Success->value,
        ]);
    }

    public function test_withdraw_fails_with_insufficient_balance(): void
    {
        $customer = $this->authCustomer(['balance' => 1000]);

        $response = $this->postJson('/api/v1/payments/wallet/withdraw', [
            'amount' => 50,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::INSUFFICIENT_BALANCE->value);
    }

    public function test_withdraw_fails_with_invalid_amount(): void
    {
        $customer = $this->authCustomer(['balance' => 10000]);

        $response = $this->postJson('/api/v1/payments/wallet/withdraw', [
            'amount' => 0,
        ]);

        $response->assertStatus(422);
    }
}
