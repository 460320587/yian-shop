<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Actions;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Actions\PayWithWalletAction;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\User\Models\Customer;
use App\Exceptions\BusinessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayWithWalletActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pays_with_wallet_and_deducts_balance(): void
    {
        $customer = Customer::factory()->create(['balance' => 10000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $service = new PaymentService();

        $payment = (new PayWithWalletAction($customer, $order, $service))->handle();

        $this->assertEquals(PaymentStatus::Success->value, $payment->status);
        $this->assertEquals('wallet', $payment->gateway);

        $customer->refresh();
        $this->assertEquals(5000, $customer->balance->amount);
    }

    public function test_transitions_order_to_paid(): void
    {
        $customer = Customer::factory()->create(['balance' => 10000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $service = new PaymentService();

        (new PayWithWalletAction($customer, $order, $service))->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid->value, $order->status);
    }

    public function test_creates_payment_log(): void
    {
        $customer = Customer::factory()->create(['balance' => 10000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $service = new PaymentService();

        $payment = (new PayWithWalletAction($customer, $order, $service))->handle();

        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'create',
        ]);
    }

    public function test_throws_when_insufficient_balance(): void
    {
        $customer = Customer::factory()->create(['balance' => 1000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $service = new PaymentService();

        $this->expectException(BusinessException::class);
        (new PayWithWalletAction($customer, $order, $service))->handle();
    }

    public function test_creates_customer_wallet_record(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $service = new PaymentService();

        // Pre-create wallet with enough balance
        \App\Domains\User\Models\CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        $payment = (new PayWithWalletAction($customer, $order, $service))->handle();

        $this->assertDatabaseHas('customer_wallets', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_creates_wallet_transaction_on_payment(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'total_amount' => 5000,
        ]);
        $service = new PaymentService();

        \App\Domains\User\Models\CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        $payment = (new PayWithWalletAction($customer, $order, $service))->handle();

        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 2, // consume
            'amount' => -5000,
            'payment_no' => $payment->payment_no,
        ]);
    }
}
