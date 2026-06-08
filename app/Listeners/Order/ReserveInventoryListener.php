<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Events\OrderCreated;
use App\Services\Inventory\InventoryService;

class ReserveInventoryListener
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function handle(OrderCreated $event): void
    {
        $this->inventoryService->reserve($event->order);
    }
}
