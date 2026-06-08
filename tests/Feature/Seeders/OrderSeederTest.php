<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_seeder_creates_orders(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
        ]);

        $this->assertDatabaseCount('orders', 6);
        $this->assertDatabaseHas('orders', ['order_no' => 'Y202601010001', 'status' => 11]);
        $this->assertDatabaseHas('orders', ['order_no' => 'Y202601010005', 'status' => 60]);
    }

    public function test_order_seeder_creates_order_items(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
        ]);

        $order = Order::where('order_no', 'Y202601010003')->first();
        $this->assertNotNull($order);
        $this->assertGreaterThanOrEqual(2, $order->items()->count());
    }

    public function test_order_seeder_has_various_statuses(): void
    {
        $this->seed([
            \Database\Seeders\ProductCategorySeeder::class,
            \Database\Seeders\ProductSeeder::class,
            \Database\Seeders\CustomerSeeder::class,
            \Database\Seeders\OrderSeeder::class,
        ]);

        $this->assertEquals(1, Order::where('status', 11)->count()); // 待付款
        $this->assertEquals(1, Order::where('status', 12)->count()); // 已付款
        $this->assertEquals(1, Order::where('status', 13)->count()); // 生产中
        $this->assertEquals(1, Order::where('status', 20)->count()); // 已发货
        $this->assertEquals(1, Order::where('status', 60)->count()); // 已完成
        $this->assertEquals(1, Order::where('status', 61)->count()); // 已取消
    }
}
