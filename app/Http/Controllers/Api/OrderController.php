<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Cart\Models\Cart;
use App\Domains\Common\ValueObjects\Money;
use App\Domains\Coupon\Models\CustomerCoupon;
use App\Domains\Coupon\Services\CouponDiscountCalculator;
use App\Domains\Logistics\Models\FreightTemplate;
use App\Domains\Order\Actions\CancelOrderAction;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Order\Models\OrderStatusLog;
use App\Domains\Product\Models\Product;
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

    public function pricing(Request $request): JsonResponse
    {
        $request->validate([
            'address_id' => ['required', 'integer', 'min:1'],
        ]);

        $customerId = auth('sanctum')->id();
        $cart = Cart::where('customer_id', $customerId)
            ->with('items')
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return $this->error(ErrorCode::CART_EMPTY, '购物车为空', null, 422);
        }

        $goodsAmount = $cart->items->sum('subtotal');
        $totalQuantity = $cart->items->sum('quantity');

        $template = FreightTemplate::where('status', 1)
            ->with('carrier')
            ->first();

        $freightAmount = 0.0;
        $carrierName = null;
        $freeThreshold = null;

        if ($template) {
            $carrierName = $template->carrier?->name;
            $freeThreshold = $template->free_threshold;

            if ($freeThreshold !== null && $goodsAmount >= (int) round((float) $freeThreshold * 100)) {
                $freightAmount = 0;
            } else {
                $firstPrice = (float) $template->first_price;
                $continuePrice = (float) $template->continue_price;
                $extraQty = max(0, $totalQuantity - 1);
                $freightAmount = (float) round($firstPrice + $extraQty * $continuePrice, 2);
            }
        }

        return $this->success([
            'freight_amount' => $freightAmount,
            'goods_amount' => (new Money($goodsAmount))->toYuan(),
            'free_threshold' => $freeThreshold,
            'carrier_name' => $carrierName,
            'calculation_type' => $template?->calculation_type,
        ]);
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

        $goodsAmount = $cart->items->sum('subtotal');
        $discountSum = 0;
        $customerCouponId = null;

        // 优惠券处理
        if ($request->filled('coupon_code')) {
            $customerCoupon = CustomerCoupon::with('coupon')
                ->where('customer_id', $customerId)
                ->where('code', $request->input('coupon_code'))
                ->first();

            if (! $customerCoupon) {
                return $this->error(ErrorCode::COUPON_NOT_FOUND, '优惠券不存在', null, 404);
            }

            if ($customerCoupon->status === 2) {
                return $this->error(ErrorCode::COUPON_ALREADY_USED, '优惠券已使用');
            }

            if ($customerCoupon->expired_at && $customerCoupon->expired_at < now()) {
                return $this->error(ErrorCode::COUPON_EXPIRED, '优惠券已过期');
            }

            $coupon = $customerCoupon->coupon;

            if (! $coupon || ! $coupon->isActive()) {
                return $this->error(ErrorCode::COUPON_NOT_APPLICABLE, '优惠券不可用');
            }

            $discountSum = CouponDiscountCalculator::calculate($coupon, $goodsAmount);

            if ($discountSum === 0 && $coupon->min_amount->amount > 0 && $goodsAmount < $coupon->min_amount->amount) {
                return $this->error(ErrorCode::COUPON_MIN_AMOUNT_NOT_MET, '订单金额未达到优惠券使用门槛', null, 422);
            }

            $customerCouponId = $customerCoupon->id;

            // 标记券为已使用
            $customerCoupon->update([
                'status' => 2,
                'used_at' => now(),
            ]);

            $coupon->increment('used_count');
        }

        $totalAmount = $goodsAmount - $discountSum;
        $orderNo = $this->generateOrderNo();

        $order = Order::create([
            'order_no' => $orderNo,
            'customer_id' => $customerId,
            'status' => OrderStatus::PendingPayment->value,
            'out_status_name' => OrderStatus::PendingPayment->label(),
            'total_amount' => $totalAmount,
            'discount_sum' => $discountSum,
            'customer_coupon_id' => $customerCouponId,
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

        \App\Events\OrderCreated::dispatch($order);

        return $this->success([
            'order_no' => $order->order_no,
            'status' => OrderStatus::PendingPayment->value,
            'customer_status' => OrderStatus::PendingPayment->label(),
            'total_amount' => (new Money($totalAmount))->toYuan(),
            'discount_sum' => (new Money($discountSum))->toYuan(),
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
            'deposit_sum' => $order->deposit_sum?->toYuan(),
            'discount_sum' => $order->discount_sum?->toYuan(),
            'express_company' => $order->express_company,
            'delivery_type' => $order->delivery_type,
            'remark' => $order->remark,
            'paid_at' => $order->paid_at,
            'submitted_at' => $order->submitted_at,
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

    public function reorder(int $id): JsonResponse
    {
        $customerId = auth('sanctum')->id();
        $order = Order::where('customer_id', $customerId)
            ->with('items')
            ->find($id);

        if (! $order) {
            return $this->error(ErrorCode::FORBIDDEN, '无权访问该订单', null, 403);
        }

        $cart = Cart::firstOrCreate(
            ['customer_id' => $customerId],
            ['total_count' => 0, 'selected_subtotal' => 0]
        );

        $validItems = $order->items->filter(function (OrderItem $item) {
            $product = Product::where('id', $item->product_id)->where('status', 1)->first();
            return $product !== null;
        });

        foreach ($validItems as $item) {
            $cart->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price->amount,
                'subtotal' => $item->total_price->amount,
                'selected' => 1,
                'spec_info' => null,
            ]);
        }

        $this->updateCartSummary($cart);

        return $this->success([], '已加入购物车');
    }

    public function statusLogs(int $id): JsonResponse
    {
        $order = Order::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $order) {
            return $this->error(ErrorCode::FORBIDDEN, '无权访问该订单', null, 403);
        }

        $logs = OrderStatusLog::where('order_id', $order->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($logs->map(fn (OrderStatusLog $log) => [
            'id' => $log->id,
            'from_status' => $log->from_status,
            'to_status' => $log->to_status,
            'remark' => $log->remark,
            'operator_type' => $log->operator_type,
            'created_at' => $log->created_at,
        ]));
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

        (new CancelOrderAction($order))->handle();

        return $this->success([], '订单取消成功');
    }

    private function updateCartSummary(Cart $cart): void
    {
        $items = $cart->items()->get();
        $cart->total_count = $items->count();
        $cart->selected_subtotal = $items->where('selected', 1)->sum('subtotal');
        $cart->save();
    }

    private function generateOrderNo(): string
    {
        $prefix = 'Y' . now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        return $prefix . $random;
    }
}
