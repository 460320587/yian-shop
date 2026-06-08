<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Coupon\Models\CustomerCoupon;
use App\Domains\Notification\Models\CustomerNotification;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    public function dashboard(): JsonResponse
    {
        $customerId = auth('sanctum')->id();

        $orderStatusCounts = [
            'pending_payment' => Order::where('customer_id', $customerId)->where('status', OrderStatus::PendingPayment->value)->count(),
            'in_progress' => Order::where('customer_id', $customerId)->whereIn('status', [
                OrderStatus::Paid->value,
                OrderStatus::InProduction->value,
            ])->count(),
            'pending_delivery' => Order::where('customer_id', $customerId)->where('status', OrderStatus::PendingDelivery->value)->count(),
            'pending_receive' => Order::where('customer_id', $customerId)->where('status', OrderStatus::PendingReceive->value)->count(),
            'pending_review' => Order::where('customer_id', $customerId)->where('status', OrderStatus::Completed->value)->count(),
            'completed' => Order::where('customer_id', $customerId)->where('status', OrderStatus::Completed->value)->count(),
        ];

        $unreadNotificationCount = CustomerNotification::where('customer_id', $customerId)
            ->where('is_read', 0)
            ->count();

        $availableCouponCount = CustomerCoupon::byCustomer($customerId)->unused()->count();

        $recentOrders = Order::where('customer_id', $customerId)
            ->with('items')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'status' => $o->status,
                'customer_status' => $o->out_status_name,
                'total_amount' => $o->total_amount->toYuan(),
                'created_at' => $o->created_at?->toDateTimeString(),
            ])
            ->all();

        return $this->success([
            'order_status_counts' => $orderStatusCounts,
            'unread_notification_count' => $unreadNotificationCount,
            'available_coupon_count' => $availableCouponCount,
            'recent_orders' => $recentOrders,
        ]);
    }
}
