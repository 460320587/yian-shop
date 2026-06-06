<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\User\Models\Customer;
use App\Domains\User\Queries\CustomerQuery;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = (new CustomerQuery($request->all()))
            ->perPage((int) $request->input('per_page', 15));

        return $this->paginated($query->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::with('addresses', 'brands')->find($id);
        if (! $customer) {
            return $this->error(ErrorCode::NOT_FOUND, '客户不存在');
        }

        return $this->success($customer);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $customer = Customer::find($id);
        if (! $customer) {
            return $this->error(ErrorCode::NOT_FOUND, '客户不存在');
        }

        $customer->update(['status' => $customer->status === 1 ? 0 : 1]);

        return $this->success(['status' => $customer->fresh()->status]);
    }
}
