<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Services;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\WalletService;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerWallet;
use App\Exceptions\BusinessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WalletService();
    }

    public function test_debit_deducts_balance(): void
    {
        $customer = Customer::factory()->create();
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        $transaction = $this->service->debit(
            $customer,
            new Money(5000),
            'consume',
            'Y202601010001',
            'P202601010001',
            '订单支付'
        );

        $wallet = CustomerWallet::where('customer_id', $customer->id)->first();
        $dbBalance = \DB::table('customers')->where('id', $customer->id)->value('balance');

        $this->assertEquals(5000, $wallet->balance->amount);
        $this->assertEquals(5000, (int) $dbBalance);
        $this->assertEquals(5000, $wallet->total_consume->amount);
    }

    public function test_debit_records_wallet_transaction(): void
    {
        $customer = Customer::factory()->create();
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        $transaction = $this->service->debit(
            $customer,
            new Money(5000),
            'consume',
            'Y202601010001',
            'P202601010001',
            '订单支付'
        );

        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 2,
            'amount' => -5000,
            'balance_before' => 10000,
            'balance_after' => 5000,
            'order_no' => 'Y202601010001',
            'payment_no' => 'P202601010001',
            'remark' => '订单支付',
        ]);
    }

    public function test_debit_throws_when_insufficient_balance(): void
    {
        $customer = Customer::factory()->create();
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 1000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('余额不足');

        $this->service->debit($customer, new Money(5000), 'consume');
    }

    public function test_credit_increases_balance(): void
    {
        $customer = Customer::factory()->create();
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 1000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        $this->service->credit(
            $customer,
            new Money(5000),
            'recharge',
            'P202601010001',
            '余额充值'
        );

        $wallet = CustomerWallet::where('customer_id', $customer->id)->first();
        $dbBalance = \DB::table('customers')->where('id', $customer->id)->value('balance');

        $this->assertEquals(6000, $wallet->balance->amount);
        $this->assertEquals(6000, (int) $dbBalance);
        $this->assertEquals(5000, $wallet->total_recharge->amount);
    }

    public function test_credit_records_recharge_transaction(): void
    {
        $customer = Customer::factory()->create();
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 1000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        $this->service->credit(
            $customer,
            new Money(5000),
            'recharge',
            'P202601010001',
            '余额充值'
        );

        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 1,
            'amount' => 5000,
            'balance_before' => 1000,
            'balance_after' => 6000,
            'payment_no' => 'P202601010001',
            'remark' => '余额充值',
        ]);
    }

    public function test_withdraw_records_withdraw_transaction(): void
    {
        $customer = Customer::factory()->create();
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        $this->service->debit(
            $customer,
            new Money(3000),
            'withdraw',
            null,
            'P202601010001',
            '余额提现'
        );

        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 4,
            'amount' => -3000,
            'balance_before' => 10000,
            'balance_after' => 7000,
            'payment_no' => 'P202601010001',
            'remark' => '余额提现',
        ]);
    }

    public function test_refund_records_refund_transaction(): void
    {
        $customer = Customer::factory()->create();
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 5000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        $this->service->credit(
            $customer,
            new Money(2000),
            'refund',
            'P202601010001',
            '订单退款'
        );

        $wallet = CustomerWallet::where('customer_id', $customer->id)->first();
        $dbBalance = \DB::table('customers')->where('id', $customer->id)->value('balance');

        $this->assertEquals(7000, $wallet->balance->amount);
        $this->assertEquals(7000, (int) $dbBalance);

        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 3,
            'amount' => 2000,
            'balance_before' => 5000,
            'balance_after' => 7000,
            'remark' => '订单退款',
        ]);
    }

    public function test_optimistic_lock_prevents_lost_update(): void
    {
        $customer = Customer::factory()->create();
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);

        // Simulate concurrent update by modifying version directly
        $wallet = CustomerWallet::where('customer_id', $customer->id)->first();
        $wallet->version = 999;
        $wallet->save();

        // The service should retry and eventually succeed
        $this->service->debit($customer, new Money(1000), 'consume');

        $wallet->refresh();

        $this->assertEquals(9000, $wallet->balance->amount);
    }
}
