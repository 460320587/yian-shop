<?php

declare(strict_types=1);

namespace App\Domains\AfterSale\Actions;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\AfterSale\Models\AfterSaleItem;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Infrastructure\Actions\BaseAction;

class ApplyAfterSaleAction extends BaseAction
{
    public function __construct(
        private readonly int $customerId,
        private readonly array $data,
    ) {
    }

    public function handle(): AfterSale
    {
        return $this->transaction(function (): AfterSale {
            $afterSale = AfterSale::create([
                'after_sale_no' => $this->generateNo(),
                'order_no' => $this->data['order_no'],
                'customer_id' => $this->customerId,
                'type' => $this->data['type'],
                'status' => 1,
                'reason' => $this->data['reason'],
                'description' => $this->data['description'] ?? null,
                'images' => $this->data['images'] ?? null,
                'refund_amount' => 0,
                'approved_amount' => 0,
            ]);

            foreach ($this->data['items'] as $item) {
                $orderItem = OrderItem::find($item['order_item_id']);
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
    }

    private function generateNo(): string
    {
        return 'A' . now()->format('Ymd') . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
