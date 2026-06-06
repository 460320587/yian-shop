<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\User\Models\Customer;
use App\Exceptions\BusinessException;
use App\Infrastructure\Actions\BaseAction;
use App\Support\ErrorCode;
use Illuminate\Support\Str;

class WithdrawWalletAction extends BaseAction
{
    public function __construct(
        private readonly Customer $customer,
        private readonly int $amount,
        private readonly PaymentService $paymentService,
    ) {
    }

    public function handle(): Payment
    {
        if ($this->customer->balance->amount < $this->amount) {
            throw new BusinessException(ErrorCode::INSUFFICIENT_BALANCE);
        }

        return $this->transaction(function (): Payment {
            $this->customer->balance = $this->customer->balance->subtract(new Money($this->amount));
            $this->customer->save();

            $payment = Payment::create([
                'payment_no' => 'P' . now()->format('Ymd') . strtoupper(Str::random(6)),
                'order_no' => null,
                'customer_id' => $this->customer->id,
                'gateway' => 'withdraw',
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
