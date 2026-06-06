<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\Payment\Services\WalletService;
use App\Domains\User\Models\Customer;
use App\Infrastructure\Actions\BaseAction;
use Illuminate\Support\Str;

class RechargeWalletAction extends BaseAction
{
    public function __construct(
        private readonly Customer $customer,
        private readonly int $amount,
        private readonly string $gateway,
        private readonly PaymentService $paymentService,
    ) {
    }

    public function handle(): Payment
    {
        $paymentNo = 'P' . now()->format('Ymd') . strtoupper(Str::random(6));

        return $this->transaction(function () use ($paymentNo): Payment {
            $walletService = new WalletService();
            $walletService->credit(
                $this->customer,
                new Money($this->amount),
                'recharge',
                $paymentNo,
                '余额充值',
            );

            $payment = Payment::create([
                'payment_no' => $paymentNo,
                'order_no' => null,
                'customer_id' => $this->customer->id,
                'gateway' => $this->gateway,
                'amount' => $this->amount,
                'status' => PaymentStatus::Success->value,
                'paid_at' => now(),
                'expire_at' => now()->addMinutes(30),
            ]);

            $this->paymentService->recordCreated($payment);

            return $payment;
        });
    }
}
