<?php

declare(strict_types=1);

namespace App\Listeners\Cache;

use App\Events\ProductStatusChanged;
use App\Services\Cache\CacheService;

class ClearProductCacheListener
{
    public function __construct(private readonly CacheService $cacheService)
    {
    }

    public function handle(ProductStatusChanged $event): void
    {
        $this->cacheService->tags(['products'])->flush();
        $this->cacheService->forget("product_{$event->product->id}");
        $this->cacheService->forget('portal_hot_products');
        $this->cacheService->forget('portal_new_arrivals');
    }
}
