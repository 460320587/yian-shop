<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Enterprise\Models\EnterpriseAuth;
use App\Domains\User\Models\Customer;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnterpriseController extends BaseController
{
    public function authStatus(): JsonResponse
    {
        $customer = auth('sanctum')->user();

        return $this->success([
            'auth_status' => $customer->auth_status,
            'auth_status_name' => $this->statusName($customer->auth_status),
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:200',
            'credit_code' => 'required|string|max:50',
            'legal_person' => 'required|string|max:50',
            'legal_person_id_card' => 'required|string|max:18',
            'business_license_img' => 'required|string|max:500',
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:20',
            'register_address' => 'nullable|string|max:500',
            'office_address' => 'nullable|string|max:500',
            'valid_date' => 'nullable|date',
        ]);

        $customerId = auth('sanctum')->id();

        $auth = DB::transaction(function () use ($customerId, $validated) {
            $auth = EnterpriseAuth::updateOrCreate(
                ['customer_id' => $customerId],
                [
                    ...$validated,
                    'auth_status' => 1,
                    'audit_remark' => null,
                ]
            );

            Customer::where('id', $customerId)->update(['auth_status' => 1]);

            return $auth;
        });

        return $this->success($auth, '认证申请已提交');
    }

    public function info(): JsonResponse
    {
        $customerId = auth('sanctum')->id();
        $auth = EnterpriseAuth::where('customer_id', $customerId)->first();

        if (! $auth) {
            return $this->error(ErrorCode::NOT_FOUND, '未找到认证信息', null, 404);
        }

        return $this->success($auth);
    }

    private function statusName(int $status): string
    {
        return match ($status) {
            0 => '未提交',
            1 => '审核中',
            2 => '认证通过',
            3 => '审核不通过',
            4 => '已驳回',
            20 => '代认证通过',
            default => '未知',
        };
    }
}
