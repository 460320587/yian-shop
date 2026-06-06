<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Actions;

use App\Domains\Payment\Actions\WithdrawWalletAction;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\User\Models\Customer;
use App\Exceptions\BusinessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawWalletActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_withdraw_decreases_balance(): void
    {
        $customer = Customer::factory()->create(['balance' => 10000]);
        $service = new PaymentService();

        $payment = (new WithdrawWalletAction($customer, 3000, $service))->handle();

        $customer->refresh();
        $this->assertEquals(7000, $customer->balance->amount);
        $this->assertEquals(PaymentStatus::Success->value, $payment->status);
        $this->assertEquals('withdraw', $payment->gateway);
    }

    public function test_throws_when_insufficient_balance(): void
    {
        $customer = Customer::factory()->create(['balance' => 1000]);
        $service = new PaymentService();

        $this->expectException(BusinessException::class);
        (new WithdrawWalletAction($customer, 3000, $service))->handle();
    }

    public function test_creates_payment_log(): void
    {
        $customer = Customer::factory()->create(['balance' => 5000]);
        $service = new PaymentService();

        $payment = (new WithdrawWalletAction($customer, 2000, $service))->handle();

        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'create',
        ]);
    }
}
