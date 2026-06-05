<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domains\Payment\Models\CustomerPayPassword;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPayPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function authCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $customer->createToken('api')->plainTextToken);
        return $customer;
    }

    public function test_user_can_set_pay_password(): void
    {
        $customer = $this->authCustomer();

        $response = $this->postJson('/api/v1/pay-password', [
            'pay_password' => '123456',
            'pay_password_confirmation' => '123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('customer_pay_passwords', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_set_pay_password_requires_confirmation(): void
    {
        $this->authCustomer();

        $response = $this->postJson('/api/v1/pay-password', [
            'pay_password' => '123456',
            'pay_password_confirmation' => '654321',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_verify_pay_password(): void
    {
        $customer = $this->authCustomer();
        $payPassword = new CustomerPayPassword();
        $payPassword->customer_id = $customer->id;
        $payPassword->setPayPassword('123456');
        $payPassword->save();

        $response = $this->postJson('/api/v1/pay-password/verify', [
            'pay_password' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.valid', true);
    }

    public function test_verify_fails_with_wrong_password(): void
    {
        $customer = $this->authCustomer();
        $payPassword = new CustomerPayPassword();
        $payPassword->customer_id = $customer->id;
        $payPassword->setPayPassword('123456');
        $payPassword->save();

        $response = $this->postJson('/api/v1/pay-password/verify', [
            'pay_password' => '000000',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.valid', false);
    }

    public function test_user_can_update_pay_password(): void
    {
        $customer = $this->authCustomer();
        $payPassword = new CustomerPayPassword();
        $payPassword->customer_id = $customer->id;
        $payPassword->setPayPassword('123456');
        $payPassword->save();

        $response = $this->putJson('/api/v1/pay-password', [
            'old_pay_password' => '123456',
            'pay_password' => '654321',
            'pay_password_confirmation' => '654321',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $payPassword->refresh();
        $this->assertTrue($payPassword->verify('654321'));
    }

    public function test_update_pay_password_requires_old_password(): void
    {
        $customer = $this->authCustomer();
        $payPassword = new CustomerPayPassword();
        $payPassword->customer_id = $customer->id;
        $payPassword->setPayPassword('123456');
        $payPassword->save();

        $response = $this->putJson('/api/v1/pay-password', [
            'old_pay_password' => '000000',
            'pay_password' => '654321',
            'pay_password_confirmation' => '654321',
        ]);

        $response->assertStatus(422);
    }

    public function test_guest_cannot_access_pay_password(): void
    {
        $response = $this->getJson('/api/v1/pay-password/status');
        $response->assertStatus(401);
    }
}
