<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Coupon\Models\Coupon;
use App\Domains\Coupon\Models\CustomerCoupon;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::available();

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        return $this->paginated($query->paginate($perPage));
    }

    public function claim(int $id): JsonResponse
    {
        $customerId = auth('sanctum')->id();

        $coupon = Coupon::find($id);
        if (! $coupon) {
            return $this->error(ErrorCode::COUPON_NOT_FOUND, '优惠券不存在', null, 404);
        }

        if ($coupon->isExpired()) {
            return $this->error(ErrorCode::COUPON_EXPIRED, '优惠券已过期');
        }

        if (! $coupon->isActive()) {
            return $this->error(ErrorCode::COUPON_NOT_APPLICABLE, '优惠券不可用');
        }

        if ($coupon->isExhausted()) {
            return $this->error(ErrorCode::COUPON_EXHAUSTED, '优惠券已领完');
        }

        if ($coupon->per_customer_limit > 0) {
            $claimedCount = CustomerCoupon::where('customer_id', $customerId)
                ->where('coupon_id', $coupon->id)
                ->count();

            if ($claimedCount >= $coupon->per_customer_limit) {
                return $this->error(ErrorCode::COUPON_OVER_LIMIT, '已达到领取上限');
            }
        }

        $customerCoupon = CustomerCoupon::create([
            'customer_id' => $customerId,
            'coupon_id' => $coupon->id,
            'code' => strtoupper(Str::random(8)),
            'status' => 1,
            'claimed_at' => now(),
            'expired_at' => $coupon->end_at,
        ]);

        $coupon->increment('claimed_count');

        return $this->success([
            'id' => $customerCoupon->id,
            'code' => $customerCoupon->code,
            'coupon' => [
                'id' => $coupon->id,
                'name' => $coupon->name,
                'type' => $coupon->type,
                'value' => $coupon->value,
            ],
            'status' => $customerCoupon->status,
            'expired_at' => $customerCoupon->expired_at,
        ], '领取成功');
    }

    public function myCoupons(Request $request): JsonResponse
    {
        $customerId = auth('sanctum')->id();

        $query = CustomerCoupon::with('coupon')
            ->byCustomer($customerId);

        if ($request->filled('status')) {
            $status = (int) $request->input('status');
            $query->where('status', $status);

            // 未使用的券额外过滤掉已过期的
            if ($status === 1) {
                $query->where(function ($q) {
                    $q->whereNull('expired_at')
                        ->orWhere('expired_at', '>', now());
                });
            }
        }

        $query->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        return $this->paginated($query->paginate($perPage));
    }
}
