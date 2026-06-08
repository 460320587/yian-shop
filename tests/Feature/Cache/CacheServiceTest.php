<?php

declare(strict_types=1);

namespace Tests\Feature\Cache;

use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    public function test_remember_stores_value_in_cache(): void
    {
        $service = new CacheService();

        $result = $service->remember('test_key', fn () => 'computed_value', 60);
        $this->assertSame('computed_value', $result);
        $this->assertSame('computed_value', Cache::get('test_key'));
    }

    public function test_remember_returns_cached_value_without_recomputing(): void
    {
        $service = new CacheService();
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;
            return 'value_' . $callCount;
        };

        $service->remember('test_key2', $callback, 60);
        $this->assertSame(1, $callCount);

        $result = $service->remember('test_key2', $callback, 60);
        $this->assertSame(1, $callCount); // not called again
        $this->assertSame('value_1', $result);
    }

    public function test_forget_removes_cached_value(): void
    {
        $service = new CacheService();
        $service->remember('test_key3', fn () => 'value', 60);
        $this->assertTrue(Cache::has('test_key3'));

        $service->forget('test_key3');
        $this->assertFalse(Cache::has('test_key3'));
    }

    public function test_forget_removes_multiple_keys(): void
    {
        $service = new CacheService();
        $service->remember('key_a', fn () => 'a', 60);
        $service->remember('key_b', fn () => 'b', 60);
        $service->remember('key_c', fn () => 'c', 60);

        $service->forget(['key_a', 'key_b']);
        $this->assertFalse(Cache::has('key_a'));
        $this->assertFalse(Cache::has('key_b'));
        $this->assertTrue(Cache::has('key_c'));
    }

    public function test_tags_caches_and_forgets_by_tag(): void
    {
        $service = new CacheService();
        $service->tags(['products'])->remember('product_1', fn () => 'p1', 60);
        $service->tags(['products'])->remember('product_2', fn () => 'p2', 60);
        $service->tags(['orders'])->remember('order_1', fn () => 'o1', 60);

        // Flush products tag
        $service->tags(['products'])->flush();

        $this->assertFalse(Cache::tags(['products'])->has('product_1'));
        $this->assertFalse(Cache::tags(['products'])->has('product_2'));
        $this->assertTrue(Cache::tags(['orders'])->has('order_1'));
    }

    public function test_warm_preloads_cache_without_returning(): void
    {
        $service = new CacheService();
        $callCount = 0;

        $service->warm('warm_key', function () use (&$callCount) {
            $callCount++;
            return 'warmed_value';
        }, 60);

        $this->assertSame(1, $callCount);
        $this->assertSame('warmed_value', Cache::get('warm_key'));
    }

    public function test_remember_with_null_callback_does_not_cache(): void
    {
        $service = new CacheService();
        $result = $service->remember('null_key', fn () => null, 60);
        $this->assertNull($result);
        // Null values should not be cached to avoid cache penetration
        $this->assertFalse(Cache::has('null_key'));
    }
}
