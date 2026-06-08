<?php

declare(strict_types=1);

namespace App\Listeners\Cache;

use App\Services\Cache\CacheService;

class ClearBannerCacheListener
{
    public function __construct(private readonly CacheService $cacheService)
    {
    }

    /**
     * 清除首页相关缓存
     */
    public function handle(): void
    {
        $this->cacheService->tags(['portal'])->flush();
    }
}
