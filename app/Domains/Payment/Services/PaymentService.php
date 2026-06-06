<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentLog;
use App\Domains\Payment\Models\WalletTransaction;
use App\Domains\User\Models\Customer;
use App\Events\PaymentSuccess;

class PaymentService
{
    public function confirm(Payment $payment, ?string $transactionNo = null, ?array $gatewayResponse = null): void
    {
        if ($payment->status === PaymentStatus::Success->value) {
            return; // 幂等
        }

        $fromStatus = $payment->status;

        $payment->update([
            'status' => PaymentStatus::Success->value,
            'transaction_no' => $transactionNo,
            'paid_at' => now(),
        ]);

        // 更新关联订单
        if ($payment->order_no) {
            Order::where('order_no', $payment->order_no)->update([
                'status' => OrderStatus::Paid->value,
                'out_status_name' => OrderStatus::Paid->label(),
                'paid_at' => now(),
            ]);
        }

        // 如果是余额支付，记录钱包流水
        if ($payment->gateway === 'wallet' && $payment->order_no) {
            $this->recordWalletTransaction($payment, 2); // 2=消费
        }

        PaymentSuccess::dispatch($payment);

        $this->writeLog($payment, 'callback', $fromStatus, PaymentStatus::Success->value, $gatewayResponse);
    }

    public function fail(Payment $payment, string $reason = '', ?array $gatewayResponse = null): void
    {
        if ($payment->status !== PaymentStatus::Pending->value) {
            return;
        }

        $fromStatus = $payment->status;

        $payment->update([
            'status' => PaymentStatus::Failed->value,
        ]);

        $this->writeLog($payment, 'callback', $fromStatus, PaymentStatus::Failed->value, $gatewayResponse);
    }

    public function recordCreated(Payment $payment): void
    {
        $this->writeLog($payment, 'create', null, $payment->status);
    }

    private function writeLog(
        Payment $payment,
        string $event,
        ?int $fromStatus,
        int $toStatus,
        ?array $gatewayResponse = null,
    ): void {
        PaymentLog::create([
            'payment_id' => $payment->id,
            'payment_no' => $payment->payment_no,
            'event' => $event,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'amount' => $payment->amount->amount,
            'gateway_response' => $gatewayResponse,
        ]);
    }

    private function recordWalletTransaction(Payment $payment, int $type): void
    {
        $customer = Customer::find($payment->customer_id);
        if (! $customer) {
            return;
        }

        WalletTransaction::create([
            'customer_id' => $payment->customer_id,
            'type' => $type,
            'amount' => $payment->amount->amount,
            'direction' => -1, // 软保留，Model 不 fillable 会被忽略
            'balance_before' => $customer->balance->amount + $payment->amount->amount,
            'balance_after' => $customer->balance->amount,
            'order_no' => $payment->order_no,
            'payment_no' => $payment->payment_no,
            'status' => 1,
            'remark' => '余额支付订单',
        ]);
    }
}
