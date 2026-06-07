<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Order\Actions\ConfirmOrderPaymentAction;
use App\Domains\Order\Actions\ShipOrderAction;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Queries\OrderQuery;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminOrderController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = (new OrderQuery($request->all()))
            ->with(['customer'])
            ->perPage((int) $request->input('per_page', 15));

        return $this->paginated($query->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with([
            'customer',
            'items.product',
            'productionSchedules',
            'orderFiles',
            'inkCoverageChecks',
            'refundRecords',
        ])->find($id);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在');
        }

        return $this->success([
            'id' => $order->id,
            'order_no' => $order->order_no,
            'customer_id' => $order->customer_id,
            'status' => $order->status,
            'out_status_name' => $order->out_status_name,
            'total_amount' => $order->total_amount?->toYuan(),
            'deposit_sum' => $order->deposit_sum?->toYuan(),
            'discount_sum' => $order->discount_sum?->toYuan(),
            'express_company' => $order->express_company,
            'delivery_type' => $order->delivery_type,
            'remark' => $order->remark,
            'paid_at' => $order->paid_at,
            'submitted_at' => $order->submitted_at,
            'created_at' => $order->created_at,
            'customer' => $order->customer,
            'items' => $order->items,
            'production_schedules' => $order->productionSchedules,
            'order_files' => $order->orderFiles,
            'ink_coverage_checks' => $order->inkCoverageChecks,
            'refund_records' => $order->refundRecords,
        ]);
    }

    public function confirmPayment(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在');
        }

        (new ConfirmOrderPaymentAction($order, auth('admin')->id()))->handle();

        return $this->success([], '已确认付款');
    }

    public function ship(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'express_company' => ['required', 'string', 'max:50'],
            'tracking_no' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->error(ErrorCode::VALIDATION_ERROR, '请求参数校验失败', $validator->errors()->toArray(), 422);
        }

        $order = Order::find($id);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在');
        }

        (new ShipOrderAction(
            $order,
            auth('admin')->id(),
            $request->input('express_company'),
            $request->input('tracking_no'),
        ))->handle();

        return $this->success([], '已发货');
    }

    public function complete(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在');
        }

        if ((int) $order->status !== OrderStatus::Shipped->value) {
            return $this->error(ErrorCode::ORDER_STATUS_INVALID, '当前订单状态不允许完成', null, 422);
        }

        $order->stateMachine()->transition($order, OrderStatus::Completed->value, [
            'operator_type' => 'admin',
            'operator_id' => auth('admin')->id(),
            'remark' => '管理员完成订单',
        ]);

        return $this->success([], '订单已完成');
    }
}
