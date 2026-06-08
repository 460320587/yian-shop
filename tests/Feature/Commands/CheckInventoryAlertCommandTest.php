<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInventoryAlertCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reports_low_stock_items(): void
    {
        $product = Product::factory()->create(['name' => '铜版纸']);
        Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 5,
            'safety_stock' => 20,
        ]);

        $this->artisan('inventory:check-alert')
            ->assertSuccessful()
            ->expectsOutputToContain('铜版纸')
            ->execute();
    }

    public function test_command_skips_safe_stock_items(): void
    {
        $product = Product::factory()->create(['name' => '安全库存商品']);
        Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 100,
            'safety_stock' => 20,
        ]);

        $this->artisan('inventory:check-alert')
            ->assertSuccessful()
            ->execute();
    }

    public function test_command_reports_multiple_low_stock_items(): void
    {
        $p1 = Product::factory()->create(['name' => '商品A']);
        $p2 = Product::factory()->create(['name' => '商品B']);
        Inventory::factory()->create([
            'product_id' => $p1->id,
            'available_qty' => 3,
            'safety_stock' => 10,
        ]);
        Inventory::factory()->create([
            'product_id' => $p2->id,
            'available_qty' => 8,
            'safety_stock' => 15,
        ]);

        $this->artisan('inventory:check-alert')
            ->assertSuccessful()
            ->expectsOutputToContain('商品A')
            ->expectsOutputToContain('商品B')
            ->execute();
    }

    public function test_command_respects_threshold_option(): void
    {
        $product = Product::factory()->create(['name' => '阈值测试']);
        Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 15,
            'safety_stock' => 20,
        ]);

        $this->artisan('inventory:check-alert', ['--threshold-ratio' => 1.0])
            ->assertSuccessful()
            ->expectsOutputToContain('阈值测试')
            ->execute();
    }

    public function test_command_shows_no_issues_when_all_safe(): void
    {
        $product = Product::factory()->create();
        Inventory::factory()->create([
            'product_id' => $product->id,
            'available_qty' => 500,
            'safety_stock' => 10,
        ]);

        $this->artisan('inventory:check-alert')
            ->assertSuccessful()
            ->expectsOutputToContain('库存检查完成')
            ->execute();
    }
}
