<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Events\OrderStatusChanged;
use App\Services\Inventory\InventoryService;

class ReleaseInventoryListener
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function handle(OrderStatusChanged $event): void
    {
        // Only release inventory when order is cancelled (61)
        if ($event->newStatus !== 61) {
            return;
        }

        $event->order->load('items');
        $this->inventoryService->release($event->order);
    }
}
