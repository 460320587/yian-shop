<?php

declare(strict_types=1);

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    private ?array $tags = null;

    /**
     * 设置缓存标签
     */
    public function tags(array $tags): self
    {
        $service = new self();
        $service->tags = $tags;
        return $service;
    }

    /**
     * 缓存读取：如果缓存存在则返回，否则执行回调并缓存结果
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $store = $this->getStore();
        $value = $store->get($key);

        if ($value !== null) {
            return $value;
        }

        $computed = $callback();

        // 不缓存 null 值，防止缓存穿透
        if ($computed !== null) {
            $store->put($key, $computed, $ttl);
        }

        return $computed;
    }

    /**
     * 缓存预热：预加载缓存但不返回值
     */
    public function warm(string $key, callable $callback, int $ttl = 3600): void
    {
        $this->remember($key, $callback, $ttl);
    }

    /**
     * 清除缓存（支持单个 key 或 key 数组）
     */
    public function forget(string|array $key): void
    {
        $store = $this->getStore();
        $keys = is_array($key) ? $key : [$key];
        foreach ($keys as $k) {
            $store->forget($k);
        }
    }

    /**
     * 清除当前标签组下的所有缓存
     */
    public function flush(): void
    {
        if ($this->tags !== null) {
            Cache::tags($this->tags)->flush();
        }
    }

    private function getStore(): \Illuminate\Contracts\Cache\Repository
    {
        if ($this->tags !== null) {
            return Cache::tags($this->tags);
        }

        return Cache::store();
    }
}
