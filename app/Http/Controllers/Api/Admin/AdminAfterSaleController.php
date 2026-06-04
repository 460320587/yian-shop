<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\AfterSale\Models\AfterSale;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAfterSaleController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = AfterSale::with('customer', 'items');

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $afterSale = AfterSale::with('customer', 'items')->find($id);
        if (! $afterSale) {
            return $this->error(ErrorCode::NOT_FOUND, '售后单不存在');
        }

        return $this->success($afterSale);
    }

    public function audit(Request $request, int $id): JsonResponse
    {
        $afterSale = AfterSale::find($id);
        if (! $afterSale) {
            return $this->error(ErrorCode::NOT_FOUND, '售后单不存在');
        }

        $data = $request->validate([
            'status' => 'required|integer|in:2,3',
            'approved_amount' => 'nullable|numeric|min:0',
            'audit_remark' => 'nullable|string|max:500',
        ]);

        $update = [
            'status' => $data['status'],
            'audit_remark' => $data['audit_remark'] ?? null,
            'auditor_id' => auth('admin')->id(),
            'audited_at' => now(),
        ];

        if (isset($data['approved_amount'])) {
            $update['approved_amount'] = \App\Domains\Common\ValueObjects\Money::fromYuan((float) $data['approved_amount']);
        }

        $afterSale->update($update);

        $label = $data['status'] === 2 ? '审核通过' : '审核拒绝';
        return $this->success($afterSale, "售后单已{$label}");
    }
}
