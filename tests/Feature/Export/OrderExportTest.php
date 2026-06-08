<?php

declare(strict_types=1);

namespace Tests\Feature\Export;

use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use App\Services\Export\ExportService;
use App\Services\Export\Exporters\OrderExport;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_orders_with_correct_headers(): void
    {
        $this->seed([
            ProductCategorySeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
        ]);

        $exporter = new OrderExport();
        $csv = (new ExportService())->toCsv($exporter);

        $lines = explode("\n", trim($csv));
        $this->assertCount(7, $lines); // header + 6 orders from OrderSeeder
        $this->assertSame("\xEF\xBB\xBF" . '订单编号,客户,状态,总金额,定金,优惠,下单时间', $lines[0]);
    }

    public function test_it_includes_all_seeded_orders(): void
    {
        $this->seed([
            ProductCategorySeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
        ]);

        $exporter = new OrderExport();
        $csv = (new ExportService())->toCsv($exporter);

        $this->assertStringContainsString('Y202601010001', $csv);
        $this->assertStringContainsString('Y202601010005', $csv);
    }
}
