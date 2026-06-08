<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners\Cache;

use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use App\Events\OrderStatusChanged;
use App\Listeners\Cache\ClearOrderCacheListener;
use App\Services\Cache\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearOrderCacheListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_clears_order_cache_on_status_change(): void
    {
        $service = new CacheService();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => 11]);

        $service->remember("order_{$order->id}", fn () => 'data', 60);
        $service->remember('admin_order_list', fn () => 'list', 60);

        $listener = new ClearOrderCacheListener($service);
        $listener->handle(new OrderStatusChanged($order, 11, 12));

        $this->assertFalse(\Illuminate\Support\Facades\Cache::has("order_{$order->id}"));
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('admin_order_list'));
    }
}
