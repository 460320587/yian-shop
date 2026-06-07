<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Gateways\PaymentGatewayFactory;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
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
            $payment = Payment::create([
                'payment_no' => $paymentNo,
                'order_no' => null,
                'customer_id' => $this->customer->id,
                'gateway' => $this->gateway,
                'amount' => $this->amount,
                'status' => PaymentStatus::Pending->value,
                'credential' => [],
                'expire_at' => now()->addMinutes(30),
            ]);

            $gateway = PaymentGatewayFactory::make($payment->gateway);
            $payment->update(['credential' => $gateway->buildCredential($payment)]);

            $this->paymentService->recordCreated($payment);

            return $payment;
        });
    }
}
