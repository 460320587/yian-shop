<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Actions;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Payment\Actions\ProcessRefundAction;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Services\WalletService;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessRefundActionTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = new WalletService();
    }

    private function createCustomerWithWallet(int $balance = 10000): Customer
    {
        $customer = Customer::factory()->create(['balance' => $balance]);
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => $balance,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);
        return $customer;
    }

    public function test_executes_refund_and_credits_wallet(): void
    {
        $customer = $this->createCustomerWithWallet(10000);
        $refund = RefundRecord::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 5000,
            'status' => 1,
            'refund_path' => 'wallet',
        ]);

        $action = new ProcessRefundAction($refund);
        $action->handle();

        $customer->refresh();
        $this->assertEquals(15000, $customer->wallet->fresh()->balance->amount);
    }

    public function test_creates_refund_wallet_transaction(): void
    {
        $customer = $this->createCustomerWithWallet(10000);
        $refund = RefundRecord::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 3000,
            'status' => 1,
            'refund_no' => 'R202601010001',
            'refund_path' => 'wallet',
        ]);

        $action = new ProcessRefundAction($refund);
        $action->handle();

        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 3, // refund
            'amount' => 3000,
            'payment_no' => 'R202601010001',
        ]);
    }

    public function test_updates_refund_record_to_completed(): void
    {
        $customer = $this->createCustomerWithWallet(10000);
        $refund = RefundRecord::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 2000,
            'status' => 1,
            'refund_path' => 'wallet',
        ]);

        $action = new ProcessRefundAction($refund);
        $action->handle();

        $refund->refresh();
        $this->assertEquals(4, $refund->status);
        $this->assertNotNull($refund->completed_at);
    }

    public function test_creates_wallet_record_when_not_exists(): void
    {
        $customer = Customer::factory()->create(['balance' => 0]);
        // 不创建 CustomerWallet
        $refund = RefundRecord::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 4000,
            'status' => 1,
            'refund_path' => 'wallet',
        ]);

        $action = new ProcessRefundAction($refund);
        $action->handle();

        $this->assertDatabaseHas('customer_wallets', [
            'customer_id' => $customer->id,
            'balance' => 4000,
        ]);
    }
}
