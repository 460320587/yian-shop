<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Cart\Models\Cart;
use App\Domains\Common\ValueObjects\Money;
use App\Domains\Coupon\Models\CustomerCoupon;
use App\Domains\Coupon\Services\CouponDiscountCalculator;
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

        // 退还优惠券
        if ($order->customer_coupon_id) {
            $customerCoupon = CustomerCoupon::with('coupon')->find($order->customer_coupon_id);
            if ($customerCoupon && $customerCoupon->status === 2) {
                $customerCoupon->update([
                    'status' => 1,
                    'used_at' => null,
                ]);

                if ($customerCoupon->coupon) {
                    $customerCoupon->coupon->decrement('used_count');
                }
            }
        }

        return $this->success([], '订单取消成功');
    }

    private function generateOrderNo(): string
    {
        $prefix = 'Y' . now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        return $prefix . $random;
    }
}
