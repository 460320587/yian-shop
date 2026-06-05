<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domains\Payment\Models\WalletTransaction;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_get_wallet_transaction_list(): void
    {
        $customer = $this->authCustomer();
        WalletTransaction::factory()->count(3)->create(['customer_id' => $customer->id]);
        $otherCustomer = Customer::factory()->create();
        WalletTransaction::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->getJson('/api/v1/wallet/transactions');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_list_can_filter_by_type(): void
    {
        $customer = $this->authCustomer();
        WalletTransaction::factory()->count(2)->create(['customer_id' => $customer->id, 'type' => 1]);
        WalletTransaction::factory()->count(3)->create(['customer_id' => $customer->id, 'type' => 2]);

        $response = $this->getJson('/api/v1/wallet/transactions?type=1');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_can_get_wallet_balance(): void
    {
        $customer = $this->authCustomer();
        $customer->balance = 50000;
        $customer->save();

        $response = $this->getJson('/api/v1/wallet/balance');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.balance', 500);
    }

    public function test_guest_cannot_access_wallet(): void
    {
        $response = $this->getJson('/api/v1/wallet/transactions');
        $response->assertStatus(401);
    }
}
