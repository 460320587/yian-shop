<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Domains\Order\Models\Order;
use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\InventoryLog;
use App\Exceptions\InsufficientInventoryException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * 预占库存：订单创建时调用
     * available↓  reserved↑
     */
    public function reserve(Order $order): void
    {
        foreach ($order->items as $item) {
            $this->updateStock(
                productId: $item->product_id,
                orderNo: $order->order_no,
                qty: $item->quantity,
                type: 1,
                availableDelta: -$item->quantity,
                reservedDelta: $item->quantity,
                lockedDelta: 0,
                reason: '订单创建预占库存',
            );
        }
    }

    /**
     * 扣减库存：支付成功时调用
     * reserved↓  locked↑
     */
    public function deduct(Order $order): void
    {
        foreach ($order->items as $item) {
            $this->updateStock(
                productId: $item->product_id,
                orderNo: $order->order_no,
                qty: $item->quantity,
                type: 2,
                availableDelta: 0,
                reservedDelta: -$item->quantity,
                lockedDelta: $item->quantity,
                reason: '支付成功扣减库存',
            );
        }
    }

    /**
     * 释放库存：订单取消/超时时调用
     * reserved↓  available↑
     */
    public function release(Order $order): void
    {
        foreach ($order->items as $item) {
            $inventory = Inventory::where('product_id', $item->product_id)->first();
            if ($inventory === null || $inventory->reserved_qty < $item->quantity) {
                continue; // Nothing to release
            }

            $this->updateStock(
                productId: $item->product_id,
                orderNo: $order->order_no,
                qty: $item->quantity,
                type: 3,
                availableDelta: $item->quantity,
                reservedDelta: -$item->quantity,
                lockedDelta: 0,
                reason: '订单取消释放库存',
            );
        }
    }

    private function updateStock(
        int $productId,
        string $orderNo,
        int $qty,
        int $type,
        int $availableDelta,
        int $reservedDelta,
        int $lockedDelta,
        string $reason,
    ): void {
        DB::transaction(function () use (
            $productId, $orderNo, $qty, $type,
            $availableDelta, $reservedDelta, $lockedDelta, $reason,
        ) {
            $inventory = Inventory::where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if ($inventory === null) {
                // Auto-create inventory record for products without one
                $inventory = Inventory::create([
                    'product_id' => $productId,
                    'available_qty' => 10000,
                    'reserved_qty' => 0,
                    'locked_qty' => 0,
                    'safety_stock' => 10,
                    'version' => 0,
                ]);
            }

            $newAvailable = $inventory->available_qty + $availableDelta;
            $newReserved = $inventory->reserved_qty + $reservedDelta;
            $newLocked = $inventory->locked_qty + $lockedDelta;

            if ($newAvailable < 0 || $newReserved < 0 || $newLocked < 0) {
                throw new InsufficientInventoryException(
                    "商品 #{$productId} 库存不足（可用: {$inventory->available_qty}, 需求: {$qty}）"
                );
            }

            $updated = Inventory::where('product_id', $productId)
                ->where('version', $inventory->version)
                ->update([
                    'available_qty' => $newAvailable,
                    'reserved_qty' => $newReserved,
                    'locked_qty' => $newLocked,
                    'version' => $inventory->version + 1,
                ]);

            if ($updated === 0) {
                throw new InsufficientInventoryException("商品 #{$productId} 库存并发冲突，请重试");
            }

            InventoryLog::create([
                'product_id' => $productId,
                'order_no' => $orderNo,
                'type' => $type,
                'qty_before' => $inventory->available_qty,
                'qty_change' => $qty,
                'qty_after' => $newAvailable,
                'reason' => $reason,
            ]);
        });
    }
}
