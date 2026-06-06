<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\User\Models\Customer;
use App\Domains\User\Models\CustomerWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_wallet(): void
    {
        $wallet = CustomerWallet::factory()->create();

        $this->assertDatabaseHas('customer_wallets', ['id' => $wallet->id]);
    }

    public function test_wallet_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $wallet = CustomerWallet::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $wallet->customer);
        $this->assertEquals($customer->id, $wallet->customer->id);
    }

    public function test_balance_is_money_cast(): void
    {
        $wallet = CustomerWallet::factory()->create(['balance' => 5000]);

        $this->assertInstanceOf(Money::class, $wallet->balance);
        $this->assertEquals(5000, $wallet->balance->amount);
    }

    public function test_available_balance_returns_money(): void
    {
        $wallet = CustomerWallet::factory()->create([
            'balance' => 10000,
            'frozen_amount' => 3000,
        ]);

        $available = $wallet->availableBalance();
        $this->assertInstanceOf(Money::class, $available);
        $this->assertEquals(7000, $available->amount);
    }

    public function test_customer_id_is_unique(): void
    {
        $customer = Customer::factory()->create();
        CustomerWallet::factory()->create(['customer_id' => $customer->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CustomerWallet::factory()->create(['customer_id' => $customer->id]);
    }

    public function test_customer_can_access_wallet_relation(): void
    {
        $customer = Customer::factory()->create();
        $wallet = CustomerWallet::factory()->create(['customer_id' => $customer->id]);

        $relatedWallet = $customer->wallet;

        $this->assertInstanceOf(CustomerWallet::class, $relatedWallet);
        $this->assertEquals($wallet->id, $relatedWallet->id);
    }

    public function test_wallet_relation_creates_wallet_when_not_exists(): void
    {
        $customer = Customer::factory()->create();

        $this->assertDatabaseMissing('customer_wallets', ['customer_id' => $customer->id]);

        $wallet = $customer->wallet;

        $this->assertInstanceOf(CustomerWallet::class, $wallet);
        $this->assertDatabaseHas('customer_wallets', ['customer_id' => $customer->id]);
        $this->assertEquals(0, $wallet->balance->amount);
    }
}
