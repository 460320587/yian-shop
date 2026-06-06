<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(array $attrs = []): Customer
    {
        $customer = Customer::factory()->create($attrs);
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);

        return $customer;
    }

    public function test_authenticated_user_can_get_balance(): void
    {
        $customer = $this->authCustomer(['balance' => 15000]);

        $response = $this->getJson('/api/v1/wallet/balance');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.balance', 150)
            ->assertJsonPath('data.customer_id', $customer->id);
    }

    public function test_guest_cannot_get_balance(): void
    {
        $response = $this->getJson('/api/v1/wallet/balance');

        $response->assertStatus(401);
    }
}
