<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners\Cache;

use App\Domains\Product\Models\Product;
use App\Events\ProductStatusChanged;
use App\Listeners\Cache\ClearProductCacheListener;
use App\Services\Cache\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearProductCacheListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_clears_product_cache_on_status_change(): void
    {
        $service = new CacheService();
        $product = Product::factory()->create(['status' => 1]);

        $service->remember("product_{$product->id}", fn () => 'data', 60);
        $service->remember('portal_hot_products', fn () => 'hot', 60);
        $service->remember('portal_new_arrivals', fn () => 'new', 60);

        $listener = new ClearProductCacheListener($service);
        $listener->handle(new ProductStatusChanged($product, 1, 0));

        $this->assertFalse(\Illuminate\Support\Facades\Cache::has("product_{$product->id}"));
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('portal_hot_products'));
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('portal_new_arrivals'));
    }
}
