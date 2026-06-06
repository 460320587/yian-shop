<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Payment\Actions\ProcessRefundAction;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Services\WalletService;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminRefundController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = RefundRecord::with(['customer', 'order']);

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        $refunds = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));

        return $this->paginated($refunds);
    }

    public function show(int $id): JsonResponse
    {
        $refund = RefundRecord::with(['customer', 'order', 'approver'])->find($id);

        if (! $refund) {
            return $this->error(ErrorCode::NOT_FOUND, '退款记录不存在');
        }

        return $this->success([
            'id' => $refund->id,
            'refund_no' => $refund->refund_no,
            'order_id' => $refund->order_id,
            'customer_id' => $refund->customer_id,
            'amount' => $refund->amount->toYuan(),
            'reason' => $refund->reason,
            'status' => $refund->status,
            'approved_by' => $refund->approved_by,
            'approved_at' => $refund->approved_at,
            'completed_at' => $refund->completed_at,
            'created_at' => $refund->created_at,
            'customer' => $refund->customer ? [
                'id' => $refund->customer->id,
                'nickname' => $refund->customer->nickname,
                'phone' => $refund->customer->phone,
            ] : null,
            'order' => $refund->order ? [
                'id' => $refund->order->id,
                'order_no' => $refund->order->order_no,
            ] : null,
            'approver' => $refund->approver ? [
                'id' => $refund->approver->id,
                'name' => $refund->approver->name,
            ] : null,
        ]);
    }

    public function audit(Request $request, int $id): JsonResponse
    {
        $refund = RefundRecord::find($id);

        if (! $refund) {
            return $this->error(ErrorCode::NOT_FOUND, '退款记录不存在');
        }

        if (! $refund->isPending()) {
            throw ValidationException::withMessages([
                'refund' => ['该退款记录不在待审核状态'],
            ]);
        }

        $data = $request->validate([
            'action' => ['required', 'string', 'in:approve,reject'],
            'remark' => ['required', 'string', 'max:500'],
        ]);

        $status = $data['action'] === 'approve' ? 1 : 2;

        $refund->update([
            'status' => $status,
            'approved_by' => auth('admin')->id(),
            'approved_at' => now(),
        ]);

        if ($data['action'] === 'approve') {
            $processAction = new ProcessRefundAction($refund, new WalletService());
            $processAction->handle();
        }

        return $this->success([
            'id' => $refund->id,
            'status' => $refund->fresh()->status,
            'approved_by' => $refund->approved_by,
            'approved_at' => $refund->approved_at,
        ], '审核完成');
    }
}
