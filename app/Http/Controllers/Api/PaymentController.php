<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\User\Models\Customer;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends BaseController
{
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'order_no' => ['required', 'string'],
            'gateway' => ['required', 'string', 'in:wechat,alipay,unionpay,wallet'],
        ]);

        $customerId = auth('sanctum')->id();
        $order = Order::where('order_no', $request->input('order_no'))
            ->where('customer_id', $customerId)
            ->first();

        if (! $order) {
            return $this->error(ErrorCode::ORDER_NOT_FOUND);
        }

        $orderStatus = OrderStatus::tryFrom((int) $order->status);
        if (! $orderStatus || ! $orderStatus->canPay()) {
            return $this->error(ErrorCode::ORDER_STATUS_INVALID, '当前订单状态不允许支付', null, 422);
        }

        $amount = $order->total_amount->amount;
        $paymentNo = 'P' . now()->format('Ymd') . strtoupper(Str::random(6));

        // wallet 支付：直接扣减余额
        if ($request->input('gateway') === 'wallet') {
            $customer = Customer::find($customerId);
            if ($customer->balance->amount < $amount) {
                return $this->error(ErrorCode::INSUFFICIENT_BALANCE, null, null, 422);
            }

            $customer->balance = $customer->balance->subtract(new Money($amount));
            $customer->save();

            $payment = Payment::create([
                'payment_no' => $paymentNo,
                'order_no' => $order->order_no,
                'customer_id' => $customerId,
                'gateway' => 'wallet',
                'amount' => $amount,
                'status' => PaymentStatus::Success->value,
                'paid_at' => now(),
                'expire_at' => now()->addMinutes(5),
            ]);

            $order->update([
                'status' => OrderStatus::Paid->value,
                'out_status_name' => OrderStatus::Paid->label(),
                'paid_at' => now(),
            ]);

            return $this->success([
                'payment_id' => $payment->id,
                'payment_no' => $payment->payment_no,
                'order_no' => $order->order_no,
                'amount' => $amount / 100,
                'gateway' => 'wallet',
                'status' => PaymentStatus::Success->value,
                'paid_at' => $payment->paid_at,
            ], '支付成功', 201);
        }

        // 其他渠道：生成 mock 凭证
        $credential = match ($request->input('gateway')) {
            'wechat' => ['type' => 'qrcode', 'qrcode_url' => 'weixin://wxpay/mock/' . $paymentNo],
            'alipay' => ['type' => 'qrcode', 'qrcode_url' => 'https://qr.alipay.com/mock/' . $paymentNo],
            'unionpay' => ['type' => 'redirect', 'redirect_url' => 'https://unionpay.com/mock/' . $paymentNo],
            default => ['type' => 'qrcode', 'qrcode_url' => 'https://mock.qrcode/' . $paymentNo],
        };

        $payment = Payment::create([
            'payment_no' => $paymentNo,
            'order_no' => $order->order_no,
            'customer_id' => $customerId,
            'gateway' => $request->input('gateway'),
            'amount' => $amount,
            'status' => PaymentStatus::Pending->value,
            'credential' => $credential,
            'expire_at' => now()->addMinutes(5),
        ]);

        return $this->success([
            'payment_id' => $payment->id,
            'payment_no' => $payment->payment_no,
            'order_no' => $order->order_no,
            'amount' => $amount / 100,
            'gateway' => $request->input('gateway'),
            'status' => PaymentStatus::Pending->value,
            'credential' => $credential,
            'expire_at' => $payment->expire_at,
        ], '支付单创建成功', 201);
    }

    public function status(int $id): JsonResponse
    {
        $payment = Payment::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $payment) {
            return $this->error(ErrorCode::FORBIDDEN, '无权访问该支付单', null, 403);
        }

        return $this->success([
            'payment_id' => $payment->id,
            'payment_no' => $payment->payment_no,
            'order_no' => $payment->order_no,
            'amount' => $payment->amount->toYuan(),
            'gateway' => $payment->gateway,
            'status' => $payment->status,
            'credential' => $payment->credential,
            'paid_at' => $payment->paid_at,
            'expire_at' => $payment->expire_at,
        ]);
    }

    public function recharge(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:50000'],
            'gateway' => ['required', 'string', 'in:wechat,alipay,unionpay'],
        ]);

        $customerId = auth('sanctum')->id();
        $customer = Customer::find($customerId);
        $amount = (int) round($request->input('amount') * 100);

        // 简化：直接增加余额（不经过真实支付渠道）
        $customer->balance = $customer->balance->add(new Money($amount));
        $customer->save();

        $paymentNo = 'P' . now()->format('Ymd') . strtoupper(Str::random(6));
        $payment = Payment::create([
            'payment_no' => $paymentNo,
            'order_no' => null,
            'customer_id' => $customerId,
            'gateway' => $request->input('gateway'),
            'amount' => $amount,
            'status' => PaymentStatus::Success->value,
            'paid_at' => now(),
            'expire_at' => now()->addMinutes(5),
        ]);

        return $this->success([
            'payment_id' => $payment->id,
            'payment_no' => $payment->payment_no,
            'amount' => $amount / 100,
            'gateway' => $request->input('gateway'),
            'status' => PaymentStatus::Success->value,
        ], '充值成功', 201);
    }

    public function mockCallback(Request $request, int $id): JsonResponse
    {
        $payment = Payment::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $payment) {
            return $this->error(ErrorCode::FORBIDDEN, '无权访问该支付单', null, 403);
        }

        $request->validate([
            'status' => ['required', 'in:success,failed'],
        ]);

        if ($request->input('status') === 'success') {
            $payment->update([
                'status' => PaymentStatus::Success->value,
                'paid_at' => now(),
            ]);

            if ($payment->order_no) {
                Order::where('order_no', $payment->order_no)->update([
                    'status' => OrderStatus::Paid->value,
                    'out_status_name' => OrderStatus::Paid->label(),
                    'paid_at' => now(),
                ]);
            }

            return $this->success([], '支付成功');
        }

        $payment->update([
            'status' => PaymentStatus::Failed->value,
        ]);

        return $this->success([], '支付失败');
    }
}
