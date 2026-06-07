<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Order\Models\InkCoverageCheck;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;

class AdminOrderFileController extends BaseController
{
    public function index(int $orderId): JsonResponse
    {
        $order = Order::find($orderId);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在');
        }

        $files = OrderFile::where('order_id', $orderId)
            ->with('brand')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($files->map(fn (OrderFile $file) => [
            'id' => $file->id,
            'order_id' => $file->order_id,
            'file_name' => $file->file_name,
            'file_url' => $file->file_url,
            'thumb_url' => $file->thumb_url,
            'file_size' => $file->file_size,
            'file_type' => $file->file_type,
            'page_count' => $file->page_count,
            'ink_coverage' => $file->ink_coverage,
            'version' => $file->version,
            'status' => $file->status,
            'archive_status' => $file->archive_status,
            'created_at' => $file->created_at,
        ]));
    }

    public function destroy(int $id): JsonResponse
    {
        $file = OrderFile::find($id);

        if (! $file) {
            return $this->error(ErrorCode::NOT_FOUND, '文件不存在');
        }

        $file->update(['status' => 0]);

        return $this->success([], '文件已删除');
    }

    public function inkCoverageChecks(int $orderId): JsonResponse
    {
        $order = Order::find($orderId);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在');
        }

        $checks = InkCoverageCheck::where('order_id', $orderId)
            ->with(['file', 'checker'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($checks->map(fn (InkCoverageCheck $check) => [
            'id' => $check->id,
            'order_id' => $check->order_id,
            'file_id' => $check->file_id,
            'check_type' => $check->check_type,
            'ink_type' => $check->ink_type,
            'coverage_c' => $check->coverage_c,
            'coverage_m' => $check->coverage_m,
            'coverage_y' => $check->coverage_y,
            'coverage_k' => $check->coverage_k,
            'total_coverage' => $check->total_coverage,
            'check_result' => $check->check_result,
            'check_report' => $check->check_report,
            'checked_by' => $check->checked_by,
            'checked_at' => $check->checked_at,
            'created_at' => $check->created_at,
        ]));
    }

    public function showInkCoverageCheck(int $id): JsonResponse
    {
        $check = InkCoverageCheck::with(['file', 'checker'])->find($id);

        if (! $check) {
            return $this->error(ErrorCode::NOT_FOUND, '检测记录不存在');
        }

        return $this->success($check);
    }
}
