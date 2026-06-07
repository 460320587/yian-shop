<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Order\Models\InkCoverageCheck;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInkCoverageCheckController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'file_id' => ['required', 'integer'],
            'check_type' => ['required', 'integer', 'in:1,2,3'],
            'ink_type' => ['nullable', 'string', 'max:32'],
            'coverage_c' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'coverage_m' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'coverage_y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'coverage_k' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_coverage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'check_result' => ['nullable', 'integer', 'in:0,1'],
            'check_report' => ['nullable', 'array'],
        ]);

        $order = Order::find($data['order_id']);
        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在');
        }

        $file = OrderFile::find($data['file_id']);
        if (! $file) {
            return $this->error(ErrorCode::NOT_FOUND, '文件不存在');
        }

        $check = InkCoverageCheck::create([
            'order_id' => $data['order_id'],
            'file_id' => $data['file_id'],
            'check_type' => $data['check_type'],
            'ink_type' => $data['ink_type'] ?? null,
            'coverage_c' => $data['coverage_c'] ?? null,
            'coverage_m' => $data['coverage_m'] ?? null,
            'coverage_y' => $data['coverage_y'] ?? null,
            'coverage_k' => $data['coverage_k'] ?? null,
            'total_coverage' => $data['total_coverage'] ?? null,
            'check_result' => $data['check_result'] ?? null,
            'check_report' => $data['check_report'] ?? null,
            'checked_by' => null,
            'checked_at' => now(),
        ]);

        return $this->success($check, '检测记录创建成功', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $check = InkCoverageCheck::find($id);

        if (! $check) {
            return $this->error(ErrorCode::NOT_FOUND, '检测记录不存在');
        }

        $data = $request->validate([
            'ink_type' => ['nullable', 'string', 'max:32'],
            'coverage_c' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'coverage_m' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'coverage_y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'coverage_k' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_coverage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'check_result' => ['nullable', 'integer', 'in:0,1'],
            'check_report' => ['nullable', 'array'],
        ]);

        $check->update($data);

        return $this->success($check->fresh(), '检测记录更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $check = InkCoverageCheck::find($id);

        if (! $check) {
            return $this->error(ErrorCode::NOT_FOUND, '检测记录不存在');
        }

        $check->delete();

        return $this->success([], '检测记录已删除');
    }
}
