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

    public function test_recharge_creates_pending_payment(): void
    {
        $customer = Customer::factory()->create(['balance' => 5000]);
        $service = new PaymentService();

        $payment = (new RechargeWalletAction($customer, 3000, 'wechat', $service))->handle();

        $this->assertEquals(PaymentStatus::Pending->value, $payment->status);
        $this->assertEquals('wechat', $payment->gateway);
        $this->assertEquals(3000, $payment->amount->amount);
        $this->assertNotNull($payment->credential);
        $this->assertArrayHasKey('qrcode_url', $payment->credential);
    }

    public function test_recharge_does_not_increase_balance_immediately(): void
    {
        $customer = Customer::factory()->create(['balance' => 5000]);
        $service = new PaymentService();

        (new RechargeWalletAction($customer, 3000, 'wechat', $service))->handle();

        $customer->refresh();
        $this->assertEquals(5000, $customer->balance->amount);
    }

    public function test_recharge_creates_payment_log(): void
    {
        $customer = Customer::factory()->create(['balance' => 0]);
        $service = new PaymentService();

        $payment = (new RechargeWalletAction($customer, 10000, 'alipay', $service))->handle();

        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'create',
        ]);
    }

    public function test_recharge_does_not_create_wallet_transaction_immediately(): void
    {
        $customer = Customer::factory()->create(['balance' => 0]);
        $service = new PaymentService();

        $payment = (new RechargeWalletAction($customer, 5000, 'wechat', $service))->handle();

        $this->assertDatabaseMissing('wallet_transactions', [
            'payment_no' => $payment->payment_no,
            'type' => 1,
        ]);
    }

    public function test_recharge_generates_gateway_credential(): void
    {
        $customer = Customer::factory()->create();
        $service = new PaymentService();

        $payment = (new RechargeWalletAction($customer, 5000, 'alipay', $service))->handle();

        $this->assertEquals('qrcode', $payment->credential['type']);
        $this->assertStringContainsString('mock', $payment->credential['qrcode_url']);
    }
}
