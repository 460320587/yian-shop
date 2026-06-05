<?php

declare(strict_types=1);

namespace App\Infrastructure\Lock;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LockManager
{
    private string $backend;

    public function __construct(string $backend = 'cache')
    {
        $this->backend = $backend;
    }

    /**
     * 获取锁
     */
    public function acquire(string $key, int $ttl = 60): bool
    {
        if ($this->backend === 'database') {
            return $this->acquireDatabaseLock($key, $ttl);
        }

        return $this->acquireCacheLock($key, $ttl);
    }

    /**
     * 释放锁
     */
    public function release(string $key): bool
    {
        if ($this->backend === 'database') {
            return $this->releaseDatabaseLock($key);
        }

        return Cache::forget($this->resolveKey($key));
    }

    /**
     * 检查锁是否被占用
     */
    public function isLocked(string $key): bool
    {
        if ($this->backend === 'database') {
            return $this->isDatabaseLocked($key);
        }

        return Cache::has($this->resolveKey($key));
    }

    /**
     * 阻塞式获取锁并执行回调
     *
     * @param string $key
     * @param int $ttl
     * @param Closure $callback
     * @return mixed
     * @throws RuntimeException
     */
    public function block(string $key, int $ttl, Closure $callback): mixed
    {
        if (!$this->acquire($key, $ttl)) {
            throw new RuntimeException("无法获取锁: {$key}");
        }

        try {
            return $callback();
        } finally {
            $this->release($key);
        }
    }

    /**
     * 使用 Cache 获取锁
     */
    private function acquireCacheLock(string $key, int $ttl): bool
    {
        $resolvedKey = $this->resolveKey($key);

        if (Cache::has($resolvedKey)) {
            return false;
        }

        Cache::put($resolvedKey, true, $ttl);
        return true;
    }

    /**
     * 使用数据库获取锁（兼容 Laravel 默认 cache_locks 表结构）
     */
    private function acquireDatabaseLock(string $key, int $ttl): bool
    {
        $resolvedKey = $this->resolveKey($key);
        $expiration = now()->addSeconds($ttl)->getTimestamp();

        // 先清理过期锁
        $this->releaseExpiredDatabaseLocks();

        try {
            DB::table('cache_locks')->insert([
                'key' => $resolvedKey,
                'owner' => $this->getOwner(),
                'expiration' => $expiration,
            ]);
            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            return false;
        }
    }

    private function releaseDatabaseLock(string $key): bool
    {
        $resolvedKey = $this->resolveKey($key);
        $deleted = DB::table('cache_locks')
            ->where('key', $resolvedKey)
            ->where('owner', $this->getOwner())
            ->delete();

        return $deleted > 0;
    }

    private function isDatabaseLocked(string $key): bool
    {
        $resolvedKey = $this->resolveKey($key);

        return DB::table('cache_locks')
            ->where('key', $resolvedKey)
            ->where('expiration', '>', now()->getTimestamp())
            ->exists();
    }

    /**
     * 清理已过期的数据库锁
     */
    private function releaseExpiredDatabaseLocks(): void
    {
        DB::table('cache_locks')
            ->where('expiration', '<=', now()->getTimestamp())
            ->delete();
    }

    /**
     * 解析锁键
     */
    private function resolveKey(string $key): string
    {
        return 'lock:' . $key;
    }

    /**
     * 获取当前锁持有者标识
     */
    private function getOwner(): string
    {
        return md5(uniqid('', true) . getmypid() . microtime(true));
    }
}
