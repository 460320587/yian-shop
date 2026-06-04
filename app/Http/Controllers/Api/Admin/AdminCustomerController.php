<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\User\Models\Customer;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('phone', 'like', "%{$keyword}%")
                    ->orWhere('nickname', 'like', "%{$keyword}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->has('vip_level')) {
            $query->where('vip_level', (int) $request->input('vip_level'));
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::with('addresses', 'brands')->find($id);
        if (! $customer) {
            return $this->error(ErrorCode::NOT_FOUND, '客户不存在');
        }

        return $this->success($customer);
    }
}
