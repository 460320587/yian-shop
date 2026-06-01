<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Cart\Models\Cart;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::where('customer_id', auth('sanctum')->id())
            ->with('items');

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        return $this->paginated($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $customerId = auth('sanctum')->id();
        $cart = Cart::where('customer_id', $customerId)
            ->with('items.product')
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return $this->error(ErrorCode::CART_EMPTY, null, null, 422);
        }

        $totalAmount = $cart->items->sum('subtotal');
        $orderNo = $this->generateOrderNo();

        $order = Order::create([
            'order_no' => $orderNo,
            'customer_id' => $customerId,
            'status' => OrderStatus::PendingPayment->value,
            'out_status_name' => OrderStatus::PendingPayment->label(),
            'total_amount' => $totalAmount,
            'source' => 1,
        ]);

        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->subtotal,
            ]);
        }

        $cart->items()->forceDelete();

        return $this->success([
            'order_no' => $order->order_no,
            'status' => OrderStatus::PendingPayment->value,
            'customer_status' => OrderStatus::PendingPayment->label(),
            'total_amount' => (new \App\Domains\Common\ValueObjects\Money($totalAmount))->toYuan(),
            'items' => $order->items->map(fn (OrderItem $item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price->toYuan(),
                'subtotal' => $item->total_price->toYuan(),
            ])->all(),
        ], '订单创建成功', 201);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::where('customer_id', auth('sanctum')->id())
            ->with('items')
            ->find($id);

        if (! $order) {
            return $this->error(ErrorCode::FORBIDDEN, '无权访问该订单', null, 403);
        }

        return $this->success([
            'id' => $order->id,
            'order_no' => $order->order_no,
            'status' => $order->status,
            'customer_status' => $order->out_status_name,
            'total_amount' => $order->total_amount->toYuan(),
            'created_at' => $order->created_at,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price->toYuan(),
                'subtotal' => $item->total_price->toYuan(),
            ])->all(),
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $order = Order::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $order) {
            return $this->error(ErrorCode::FORBIDDEN, '无权访问该订单', null, 403);
        }

        $status = OrderStatus::from((int) $order->status);

        if (! $status->canCancel()) {
            return $this->error(ErrorCode::ORDER_STATUS_INVALID, '当前订单状态不允许取消', null, 422);
        }

        $order->update([
            'status' => OrderStatus::Cancelled->value,
            'out_status_name' => OrderStatus::Cancelled->label(),
        ]);

        return $this->success([], '订单取消成功');
    }

    private function generateOrderNo(): string
    {
        $prefix = 'Y' . now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        return $prefix . $random;
    }
}
