<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Sample\Models\SampleOrder;
use App\Domains\Sample\Queries\SampleOrderQuery;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSampleOrderController extends BaseController
{
    private const VALID_TRANSITIONS = [
        100 => [105],           // 待付款 → 已取消
        101 => [102, 103, 105], // 已付款 → 待发货/已发货/已取消
        102 => [103, 105],      // 待发货 → 已发货/已取消
        103 => [104],           // 已发货 → 已完成
    ];

    public function index(Request $request): JsonResponse
    {
        $query = (new SampleOrderQuery($request->all()))
            ->with(['customer', 'product'])
            ->perPage((int) $request->input('per_page', 15));

        return $this->paginated($query->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $order = SampleOrder::with(['customer', 'product'])->find($id);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '样品订单不存在');
        }

        return $this->success($order);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = SampleOrder::find($id);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '样品订单不存在');
        }

        $data = $request->validate([
            'status' => ['required', 'integer', 'in:102,103,104,105'],
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        $targetStatus = (int) $data['status'];
        $currentStatus = (int) $order->status;

        if (! $this->canTransition($currentStatus, $targetStatus)) {
            return $this->error(ErrorCode::ORDER_STATUS_INVALID, '无效的状态流转', null, 422);
        }

        $updates = ['status' => $targetStatus];

        match ($targetStatus) {
            103 => $updates['shipped_at'] = now(),
            104 => $updates['completed_at'] = now(),
            105 => $updates['cancelled_at'] = now(),
            default => null,
        };

        $order->update($updates);

        return $this->success(['id' => $order->id, 'status' => $order->status], '状态更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $order = SampleOrder::find($id);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '样品订单不存在');
        }

        $order->delete();

        return $this->success([], '样品订单已删除');
    }

    private function canTransition(int $from, int $to): bool
    {
        return isset(self::VALID_TRANSITIONS[$from]) && in_array($to, self::VALID_TRANSITIONS[$from], true);
    }
}
