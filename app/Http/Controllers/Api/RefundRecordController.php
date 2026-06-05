<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RefundRecordController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $customerId = auth('sanctum')->id();

        $query = RefundRecord::where('customer_id', $customerId)
            ->with(['order', 'payment'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        return $this->paginated($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $customerId = auth('sanctum')->id();

        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        $order = Order::find($validated['order_id']);
        $payment = Payment::find($validated['payment_id']);

        if (! $order || $order->customer_id !== $customerId) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该订单');
        }

        if (! $payment || $payment->customer_id !== $customerId || $payment->order_no !== $order->order_no) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该支付记录');
        }

        $refund = RefundRecord::create([
            'order_id' => $validated['order_id'],
            'payment_id' => $validated['payment_id'],
            'customer_id' => $customerId,
            'refund_no' => $this->generateRefundNo(),
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'status' => 0,
            'refund_path' => 'original',
        ]);

        return $this->success($this->transformRefund($refund), '退款申请已提交', 201);
    }

    public function show(int $id): JsonResponse
    {
        $customerId = auth('sanctum')->id();

        $refund = RefundRecord::where('customer_id', $customerId)
            ->with(['order', 'payment'])
            ->find($id);

        if (! $refund) {
            return $this->error(ErrorCode::NOT_FOUND, '退款记录不存在', null, 404);
        }

        return $this->success($this->transformRefund($refund));
    }

    private function generateRefundNo(): string
    {
        return 'R' . now()->format('Ymd') . strtoupper(Str::random(6));
    }

    private function transformRefund(RefundRecord $refund): array
    {
        return [
            'id' => $refund->id,
            'refund_no' => $refund->refund_no,
            'order_id' => $refund->order_id,
            'payment_id' => $refund->payment_id,
            'amount' => $refund->amount->toYuan(),
            'reason' => $refund->reason,
            'status' => $refund->status,
            'refund_path' => $refund->refund_path,
            'gateway_refund_no' => $refund->gateway_refund_no,
            'approved_at' => $refund->approved_at,
            'completed_at' => $refund->completed_at,
            'created_at' => $refund->created_at,
        ];
    }
}
