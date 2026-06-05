<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Models;

use App\Domains\Enterprise\Models\CustomerBrand;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_order_file(): void
    {
        $file = OrderFile::factory()->create();

        $this->assertDatabaseHas('order_files', ['id' => $file->id]);
    }

    public function test_order_file_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $file = OrderFile::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $file->order);
        $this->assertEquals($order->id, $file->order->id);
    }

    public function test_order_file_belongs_to_brand(): void
    {
        $brand = CustomerBrand::factory()->create();
        $file = OrderFile::factory()->create(['brand_id' => $brand->id]);

        $this->assertInstanceOf(CustomerBrand::class, $file->brand);
        $this->assertEquals($brand->id, $file->brand->id);
    }

    public function test_casts_are_correct(): void
    {
        $file = new OrderFile();
        $casts = $file->getCasts();

        $this->assertArrayHasKey('page_count', $casts);
        $this->assertArrayHasKey('ink_coverage', $casts);
        $this->assertArrayHasKey('file_size', $casts);
        $this->assertArrayHasKey('archive_status', $casts);
        $this->assertArrayHasKey('version', $casts);
    }
}
