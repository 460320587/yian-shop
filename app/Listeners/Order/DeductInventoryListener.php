<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Domains\Order\Models\Order;
use App\Events\PaymentSuccess;
use App\Services\Inventory\InventoryService;

class DeductInventoryListener
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function handle(PaymentSuccess $event): void
    {
        $order = Order::where('order_no', $event->payment->order_no)->first();

        if ($order === null) {
            return;
        }

        $order->load('items');
        $this->inventoryService->deduct($order);
    }
}
