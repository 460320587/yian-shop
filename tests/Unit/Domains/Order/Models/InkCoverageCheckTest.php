<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Models;

use App\Domains\Order\Models\InkCoverageCheck;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InkCoverageCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_check(): void
    {
        $check = InkCoverageCheck::factory()->create();
        $this->assertDatabaseHas('ink_coverage_checks', ['id' => $check->id]);
    }

    public function test_check_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $check = InkCoverageCheck::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $check->order);
    }

    public function test_check_belongs_to_file(): void
    {
        $file = OrderFile::factory()->create();
        $check = InkCoverageCheck::factory()->create([
            'order_id' => $file->order_id,
            'file_id' => $file->id,
        ]);

        $this->assertInstanceOf(OrderFile::class, $check->file);
    }

    public function test_coverage_values_are_floats(): void
    {
        $check = InkCoverageCheck::factory()->create([
            'coverage_c' => 12.34,
            'coverage_m' => 23.45,
            'coverage_y' => 34.56,
            'coverage_k' => 45.67,
            'total_coverage' => 28.51,
        ]);

        $this->assertSame(12.34, $check->coverage_c);
        $this->assertSame(23.45, $check->coverage_m);
        $this->assertSame(34.56, $check->coverage_y);
        $assertK = $check->coverage_k;
        $this->assertTrue(abs($assertK - 45.67) < 0.001);
    }

    public function test_check_report_is_cast_to_array(): void
    {
        $check = InkCoverageCheck::factory()->create(['check_report' => ['pages' => 50]]);
        $this->assertIsArray($check->check_report);
    }
}
