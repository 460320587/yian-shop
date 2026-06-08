<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\Payment\Webhooks\WebhookVerifierFactory;
use App\Http\Controllers\BaseController;
use App\Infrastructure\Lock\LockManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaymentWebhookController extends BaseController
{
    public function __construct(
        private PaymentService $paymentService,
    ) {
    }

    public function wechatPay(Request $request): JsonResponse
    {
        try {
            $verifier = WebhookVerifierFactory::make('wechat');
            $verifier->verify($request);
        } catch (ValidationException $e) {
            return response()->json(['code' => 'FAIL', 'message' => '签名验证失败'], 422);
        }

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

        try {
            app(LockManager::class)->block("webhook:wechat:{$payment->payment_no}", 30, function () use ($payment, $payload) {
                if ($payload['trade_state'] === 'SUCCESS') {
                    $this->paymentService->confirm(
                        $payment,
                        $payload['transaction_id'] ?? null,
                        $payload,
                    );
                }
            });
        } catch (RuntimeException $e) {
            return response()->json(['code' => 'FAIL', 'message' => '处理中'], 429);
        }

        return response()->json(['code' => 'SUCCESS']);
    }

    public function alipay(Request $request): Response
    {
        try {
            $verifier = WebhookVerifierFactory::make('alipay');
            $verifier->verify($request);
        } catch (ValidationException $e) {
            return response('fail', 422);
        }

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

        try {
            app(LockManager::class)->block("webhook:alipay:{$payment->payment_no}", 30, function () use ($payment, $payload, $successStatuses) {
                if (in_array($payload['trade_status'], $successStatuses, true)) {
                    // 金额校验（支付宝返回元）
                    if (isset($payload['total_amount'])) {
                        $amountYuan = (int) round((float) $payload['total_amount'] * 100);
                        if ($amountYuan !== $payment->amount->amount) {
                            throw new \InvalidArgumentException('金额不匹配');
                        }
                    }

                    $this->paymentService->confirm(
                        $payment,
                        $payload['trade_no'] ?? null,
                        $payload,
                    );
                }
            });
        } catch (RuntimeException $e) {
            return response('fail', 429);
        } catch (\InvalidArgumentException $e) {
            return response('fail');
        }

        return response('success');
    }
}
