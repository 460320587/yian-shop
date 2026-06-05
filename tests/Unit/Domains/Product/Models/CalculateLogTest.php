<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Product\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Product\Models\CalculateLog;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_log(): void
    {
        $log = CalculateLog::factory()->create();
        $this->assertDatabaseHas('calculate_logs', ['id' => $log->id]);
    }

    public function test_log_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $log = CalculateLog::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $log->order);
    }

    public function test_log_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $log = CalculateLog::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $log->product);
    }

    public function test_params_is_cast_to_array(): void
    {
        $log = CalculateLog::factory()->create(['params' => ['a' => 1]]);
        $this->assertIsArray($log->params);
    }
}
