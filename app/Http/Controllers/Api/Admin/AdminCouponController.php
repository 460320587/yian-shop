<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Coupon\Models\Coupon;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCouponController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::query();

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', (int) $request->input('type'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        return $this->paginated($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'required|integer|in:1,2,3',
            'value' => 'required|integer|min:0',
            'min_amount' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'total_count' => 'nullable|integer',
            'per_customer_limit' => 'nullable|integer',
            'status' => 'required|integer|in:1,2',
        ]);

        $data['min_amount'] = $data['min_amount'] ?? 0;
        $data['max_discount'] = $data['max_discount'] ?? 0;
        $data['total_count'] = $data['total_count'] ?? -1;
        $data['per_customer_limit'] = $data['per_customer_limit'] ?? -1;

        $coupon = Coupon::create($data);

        return $this->success($coupon, '优惠券创建成功', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (! $coupon) {
            return $this->error(\App\Support\ErrorCode::NOT_FOUND, '优惠券不存在', null, 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'sometimes|nullable|string',
            'value' => 'sometimes|integer|min:0',
            'min_amount' => 'sometimes|integer|min:0',
            'max_discount' => 'sometimes|integer|min:0',
            'start_at' => 'sometimes|date',
            'end_at' => 'sometimes|date|after:start_at',
            'total_count' => 'sometimes|integer',
            'per_customer_limit' => 'sometimes|integer',
            'status' => 'sometimes|integer|in:1,2',
        ]);

        $coupon->update($data);

        return $this->success($coupon, '优惠券更新成功');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (! $coupon) {
            return $this->error(\App\Support\ErrorCode::NOT_FOUND, '优惠券不存在', null, 404);
        }

        $coupon->update(['status' => $coupon->status === 1 ? 2 : 1]);

        return $this->success(['status' => $coupon->status], '状态切换成功');
    }
}
