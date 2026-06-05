<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookController extends BaseController
{
    public function __construct(
        private PaymentService $paymentService,
    ) {
    }

    public function wechatPay(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (empty($payload['out_trade_no']) || empty($payload['trade_state'])) {
            return response()->json(['code' => 'FAIL', 'message' => '参数缺失']);
        }

        $payment = Payment::where('payment_no', $payload['out_trade_no'])
            ->where('gateway', 'wechat')
            ->first();

        if (! $payment) {
            return response()->json(['code' => 'FAIL', 'message' => '支付单不存在']);
        }

        // 金额校验
        if (isset($payload['total_fee']) && (int) $payload['total_fee'] !== $payment->amount->amount) {
            return response()->json(['code' => 'FAIL', 'message' => '金额不匹配'], 400);
        }

        if ($payload['trade_state'] === 'SUCCESS') {
            $this->paymentService->confirm(
                $payment,
                $payload['transaction_id'] ?? null,
                $payload,
            );
        }

        return response()->json(['code' => 'SUCCESS']);
    }

    public function alipay(Request $request): Response
    {
        $payload = $request->all();

        if (empty($payload['out_trade_no']) || empty($payload['trade_status'])) {
            return response('fail');
        }

        $payment = Payment::where('payment_no', $payload['out_trade_no'])
            ->where('gateway', 'alipay')
            ->first();

        if (! $payment) {
            return response('success'); // 支付宝要求即使单不存在也返回 success
        }

        $successStatuses = ['TRADE_SUCCESS', 'TRADE_FINISHED'];

        if (in_array($payload['trade_status'], $successStatuses, true)) {
            // 金额校验（支付宝返回元）
            if (isset($payload['total_amount'])) {
                $amountYuan = (int) round((float) $payload['total_amount'] * 100);
                if ($amountYuan !== $payment->amount->amount) {
                    return response('fail');
                }
            }

            $this->paymentService->confirm(
                $payment,
                $payload['trade_no'] ?? null,
                $payload,
            );
        }

        return response('success');
    }
}
