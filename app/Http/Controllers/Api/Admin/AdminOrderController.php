<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Order\Actions\ConfirmOrderPaymentAction;
use App\Domains\Order\Actions\ShipOrderAction;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminOrderController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with('customer');

        if ($request->filled('order_no')) {
            $query->where('order_no', 'like', '%' . $request->input('order_no') . '%');
        }

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', (int) $request->input('customer_id'));
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::with('customer', 'items')->find($id);
        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在');
        }

        return $this->success($order);
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
