<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\User\Models\Customer;
use App\Exceptions\BusinessException;
use App\Infrastructure\Actions\BaseAction;
use App\Support\ErrorCode;
use Illuminate\Support\Str;

class PayWithWalletAction extends BaseAction
{
    public function __construct(
        private readonly Customer $customer,
        private readonly Order $order,
        private readonly PaymentService $paymentService,
    ) {
    }

    public function handle(): Payment
    {
        $amount = $this->order->total_amount->amount;

        if ($this->customer->balance->amount < $amount) {
            throw new BusinessException(ErrorCode::INSUFFICIENT_BALANCE);
        }

        return $this->transaction(function () use ($amount): Payment {
            $this->customer->balance = $this->customer->balance->subtract(new Money($amount));
            $this->customer->save();

            $payment = Payment::create([
                'payment_no' => 'P' . now()->format('Ymd') . strtoupper(Str::random(6)),
                'order_no' => $this->order->order_no,
                'customer_id' => $this->customer->id,
                'gateway' => 'wallet',
                'amount' => $amount,
                'status' => PaymentStatus::Success->value,
                'paid_at' => now(),
                'expire_at' => now()->addMinutes(30),
            ]);

            $this->paymentService->recordCreated($payment);

            $this->order->stateMachine()->transition($this->order, OrderStatus::Paid->value, [
                'operator_type' => 'customer',
                'operator_id' => null,
                'remark' => '钱包支付成功',
                'paid_at' => now(),
            ]);

            return $payment;
        });
    }
}
