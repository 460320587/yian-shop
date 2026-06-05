<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Enterprise\Models\EnterpriseAuth;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminEnterpriseAuthController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = EnterpriseAuth::with('customer');

        if ($request->has('auth_status')) {
            $query->where('auth_status', (int) $request->input('auth_status'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('company_name', 'like', "%{$keyword}%")
                  ->orWhere('credit_code', 'like', "%{$keyword}%");
            });
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $auth = EnterpriseAuth::with('customer')->find($id);

        if (! $auth) {
            return $this->error(ErrorCode::NOT_FOUND, '企业认证记录不存在');
        }

        return $this->success([
            'id' => $auth->id,
            'customer_id' => $auth->customer_id,
            'company_name' => $auth->company_name,
            'credit_code' => $auth->credit_code,
            'legal_person' => $auth->legal_person,
            'legal_person_id_card' => $auth->legal_person_id_card,
            'business_license_img' => $auth->business_license_img,
            'contact_name' => $auth->contact_name,
            'contact_phone' => $auth->contact_phone,
            'register_address' => $auth->register_address,
            'office_address' => $auth->office_address,
            'valid_date' => $auth->valid_date,
            'auth_status' => $auth->auth_status,
            'audit_remark' => $auth->audit_remark,
            'created_at' => $auth->created_at,
            'customer' => $auth->customer ? [
                'id' => $auth->customer->id,
                'nickname' => $auth->customer->nickname,
                'phone' => $auth->customer->phone,
            ] : null,
        ]);
    }

    public function audit(Request $request, int $id): JsonResponse
    {
        $auth = EnterpriseAuth::find($id);

        if (! $auth) {
            return $this->error(ErrorCode::NOT_FOUND, '企业认证记录不存在');
        }

        // 只允许审核状态为 1（审核中）的记录
        if ((int) $auth->auth_status !== 1) {
            throw ValidationException::withMessages([
                'auth_status' => ['该认证记录不在审核中状态'],
            ]);
        }

        $data = $request->validate([
            'auth_status' => ['required', 'integer', 'in:2,3,4'],
            'audit_remark' => ['required', 'string', 'max:500'],
        ]);

        $auth->update([
            'auth_status' => $data['auth_status'],
            'audit_remark' => $data['audit_remark'],
        ]);

        return $this->success([
            'id' => $auth->id,
            'auth_status' => $auth->auth_status,
            'audit_remark' => $auth->audit_remark,
        ], '审核完成');
    }
}
