<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\Payment\Services\WalletService;
use App\Domains\User\Models\Customer;
use App\Infrastructure\Actions\BaseAction;
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
        $paymentNo = 'P' . now()->format('Ymd') . strtoupper(Str::random(6));

        return $this->transaction(function () use ($amount, $paymentNo): Payment {
            $walletService = new WalletService();
            $walletService->debit(
                $this->customer,
                new Money($amount),
                'consume',
                $this->order->order_no,
                $paymentNo,
                '钱包支付订单',
            );

            $payment = Payment::create([
                'payment_no' => $paymentNo,
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
