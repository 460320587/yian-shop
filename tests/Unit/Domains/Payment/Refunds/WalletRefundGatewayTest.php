<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Refunds;

use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Refunds\WalletRefundGateway;
use App\Domains\Payment\Services\WalletService;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletRefundGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_correct_path(): void
    {
        $gateway = new WalletRefundGateway(new WalletService());
        $this->assertSame('wallet', $gateway->getPath());
    }

    public function test_refund_credits_customer_wallet(): void
    {
        $customer = Customer::factory()->create(['balance' => 10000]);
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);
        $refund = RefundRecord::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 5000,
            'refund_path' => 'wallet',
        ]);

        $gateway = new WalletRefundGateway(new WalletService());
        $response = $gateway->refund($refund);

        $this->assertSame('success', $response['status']);
        $customer->refresh();
        $this->assertEquals(15000, $customer->wallet->fresh()->balance->amount);
    }

    public function test_refund_creates_wallet_transaction(): void
    {
        $customer = Customer::factory()->create(['balance' => 10000]);
        CustomerWallet::create([
            'customer_id' => $customer->id,
            'balance' => 10000,
            'frozen_amount' => 0,
            'total_recharge' => 0,
            'total_consume' => 0,
            'status' => 1,
            'version' => 0,
        ]);
        $refund = RefundRecord::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 3000,
            'refund_path' => 'wallet',
            'refund_no' => 'R202601010001',
        ]);

        $gateway = new WalletRefundGateway(new WalletService());
        $gateway->refund($refund);

        $this->assertDatabaseHas('wallet_transactions', [
            'customer_id' => $customer->id,
            'type' => 3, // refund
            'amount' => 3000,
            'payment_no' => 'R202601010001',
        ]);
    }
}
