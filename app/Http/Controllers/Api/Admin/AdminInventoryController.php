<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\InventoryLog;
use App\Domains\Product\Models\Product;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminInventoryController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Inventory::with('product:id,name,code');

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->whereHas('product', function ($q) use ($keyword): void {
                $q->where('name', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->boolean('low_stock_only')) {
            $query->whereRaw('available_qty <= safety_stock');
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator->through(function (Inventory $item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'product_code' => $item->product?->code,
                'available_qty' => $item->available_qty,
                'reserved_qty' => $item->reserved_qty,
                'locked_qty' => $item->locked_qty,
                'safety_stock' => $item->safety_stock,
                'is_low_stock' => $item->available_qty <= $item->safety_stock,
                'updated_at' => $item->updated_at,
            ];
        }));
    }

    public function adjust(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'qty_change' => ['required', 'integer', 'min:-100000', 'max:100000', 'not_in:0'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        $inventory = Inventory::with('product')->find($id);

        if (! $inventory) {
            return $this->error(ErrorCode::NOT_FOUND, '库存记录不存在');
        }

        $qtyChange = (int) $data['qty_change'];
        $newQty = $inventory->available_qty + $qtyChange;

        if ($newQty < 0) {
            throw ValidationException::withMessages([
                'qty_change' => ['调整后库存不能为负数'],
            ]);
        }

        $adminId = auth('admin')->id();

        InventoryLog::create([
            'product_id' => $inventory->product_id,
            'order_no' => null,
            'type' => 5, // 盘点调整
            'qty_before' => $inventory->available_qty,
            'qty_change' => $qtyChange,
            'qty_after' => $newQty,
            'reason' => $data['reason'],
            'created_by' => $adminId,
        ]);

        $inventory->update(['available_qty' => $newQty]);

        return $this->success([
            'id' => $inventory->id,
            'available_qty' => $newQty,
        ], '库存调整成功');
    }

    public function logs(Request $request): JsonResponse
    {
        $query = InventoryLog::with(['product:id,name', 'operator:id,name'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', (int) $request->input('type'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator->through(function (InventoryLog $log) {
            return [
                'id' => $log->id,
                'product_id' => $log->product_id,
                'product_name' => $log->product?->name,
                'order_no' => $log->order_no,
                'type' => $log->type,
                'type_label' => $this->typeLabel($log->type),
                'qty_before' => $log->qty_before,
                'qty_change' => $log->qty_change,
                'qty_after' => $log->qty_after,
                'reason' => $log->reason,
                'operator_name' => $log->operator?->name,
                'created_at' => $log->created_at,
            ];
        }));
    }

    private function typeLabel(int $type): string
    {
        return match ($type) {
            1 => '预占',
            2 => '扣减',
            3 => '释放',
            4 => '返还',
            5 => '盘点调整',
            default => '其他',
        };
    }
}
