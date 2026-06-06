<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Actions;

use App\Domains\Payment\Actions\RechargeWalletAction;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RechargeWalletActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recharge_increases_balance(): void
    {
        $customer = Customer::factory()->create(['balance' => 5000]);
        $service = new PaymentService();

        $payment = (new RechargeWalletAction($customer, 3000, 'wechat', $service))->handle();

        $customer->refresh();
        $this->assertEquals(8000, $customer->balance->amount);
        $this->assertEquals(PaymentStatus::Success->value, $payment->status);
        $this->assertEquals('wechat', $payment->gateway);
    }

    public function test_creates_payment_log(): void
    {
        $customer = Customer::factory()->create(['balance' => 0]);
        $service = new PaymentService();

        $payment = (new RechargeWalletAction($customer, 10000, 'alipay', $service))->handle();

        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'create',
        ]);
    }

    public function test_creates_customer_wallet_record(): void
    {
        $customer = Customer::factory()->create(['balance' => 0]);
        $service = new PaymentService();

        $payment = (new RechargeWalletAction($customer, 5000, 'wechat', $service))->handle();

        $this->assertDatabaseHas('customer_wallets', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_creates_recharge_wallet_transaction(): void
    {
        $customer = Customer::factory()->create(['balance' => 0]);
        $service = new PaymentService();

        $payment = (new RechargeWalletAction($customer, 5000, 'wechat', $service))->handle();

        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 1, // recharge
            'amount' => 5000,
            'payment_no' => $payment->payment_no,
        ]);
    }
}
