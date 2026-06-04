<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\AfterSale\Models\AfterSaleItem;
use App\Domains\Order\Models\Order;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AfterSaleController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = AfterSale::with('items')
            ->where('customer_id', auth('sanctum')->id())
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        return $this->paginated($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_no' => 'required|string|exists:orders,order_no',
            'type' => 'required|integer|in:1,2,3,4,5',
            'reason' => 'required|string|max:500',
            'description' => 'nullable|string|max:2000',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $customerId = auth('sanctum')->id();

        // 校验订单归属
        $order = Order::where('order_no', $validated['order_no'])
            ->where('customer_id', $customerId)
            ->first();

        if (! $order) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该订单', null, 403);
        }

        $afterSale = DB::transaction(function () use ($customerId, $validated) {
            $afterSale = AfterSale::create([
                'after_sale_no' => $this->generateAfterSaleNo(),
                'order_no' => $validated['order_no'],
                'customer_id' => $customerId,
                'type' => $validated['type'],
                'status' => 1,
                'reason' => $validated['reason'],
                'description' => $validated['description'] ?? null,
                'images' => $validated['images'] ?? null,
                'refund_amount' => 0,
                'approved_amount' => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $orderItem = \App\Domains\Order\Models\OrderItem::find($item['order_item_id']);
                AfterSaleItem::create([
                    'after_sale_id' => $afterSale->id,
                    'order_item_id' => $item['order_item_id'],
                    'product_name' => $orderItem?->product?->name ?? '未知商品',
                    'quantity' => $item['quantity'],
                    'unit_refund' => $orderItem?->unit_price ?? 0,
                ]);
            }

            return $afterSale;
        });

        return $this->success($afterSale->load('items'), '售后申请已提交', 201);
    }

    public function show(int $id): JsonResponse
    {
        $afterSale = AfterSale::with('items')
            ->where('customer_id', auth('sanctum')->id())
            ->find($id);

        if (! $afterSale) {
            return $this->error(ErrorCode::NOT_FOUND, '售后单不存在', null, 404);
        }

        return $this->success($afterSale);
    }

    public function cancel(int $id): JsonResponse
    {
        $afterSale = AfterSale::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $afterSale) {
            return $this->error(ErrorCode::NOT_FOUND, '售后单不存在', null, 404);
        }

        if (! $afterSale->canCancel()) {
            return $this->error(ErrorCode::ORDER_STATUS_INVALID, '当前状态不可取消', null, 422);
        }

        $afterSale->update(['status' => 6]);

        return $this->success([], '售后单已关闭');
    }

    private function generateAfterSaleNo(): string
    {
        return 'A' . now()->format('Ymd') . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
