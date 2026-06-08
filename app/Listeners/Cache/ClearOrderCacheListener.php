<?php

declare(strict_types=1);

namespace App\Listeners\Cache;

use App\Events\OrderStatusChanged;
use App\Services\Cache\CacheService;

class ClearOrderCacheListener
{
    public function __construct(private readonly CacheService $cacheService)
    {
    }

    public function handle(OrderStatusChanged $event): void
    {
        $this->cacheService->forget("order_{$event->order->id}");
        $this->cacheService->forget('admin_order_list');
    }
}
