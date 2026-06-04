<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Order\Models\Order;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
