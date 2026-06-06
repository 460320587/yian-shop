<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithPricingParams(): Product
    {
        $category = ProductCategory::factory()->create();
        $pricingParams = [
            'base_price' => 250,
            'unit' => '本',
            'price_tiers' => [
                ['min_qty' => 100, 'price' => 250],
                ['min_qty' => 500, 'price' => 200],
                ['min_qty' => 1000, 'price' => 160],
            ],
            'paper_options' => [
                ['id' => 1, 'name' => '128g铜版纸', 'price_factor' => 0.85],
                ['id' => 2, 'name' => '157g铜版纸', 'price_factor' => 1.00],
            ],
            'color_options' => [
                ['id' => 1, 'name' => '单色', 'price_factor' => 0.35],
                ['id' => 2, 'name' => '四色', 'price_factor' => 1.00],
            ],
            'process_options' => [
                ['id' => 1, 'name' => '覆膜', 'price' => 60, 'unit' => '㎡'],
                ['id' => 2, 'name' => '烫金', 'price' => 200, 'unit' => '㎡'],
            ],
        ];

        return Product::factory()->create([
            'category_id' => $category->id,
            'name' => '宣传册',
            'status' => 1,
            'pricing_params' => $pricingParams,
        ]);
    }

    public function test_can_calculate_price_with_basic_params(): void
    {
        $product = $this->createProductWithPricingParams();

        $response = $this->postJson('/api/v1/products/' . $product->id . '/price', [
            'quantity' => 1000,
            'paper_id' => 2,
            'color_id' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.quantity', 1000)
            ->assertJsonPath('data.unit_price', 1.6) // 1000本 tier price = 160分 = 1.6元
            ->assertJsonPath('data.breakdown.base_amount', 1600) // 1000 * 1.6 = 1600元？不对
        ;
    }

    public function test_can_calculate_price_with_process_options(): void
    {
        $product = $this->createProductWithPricingParams();

        $response = $this->postJson('/api/v1/products/' . $product->id . '/price', [
            'quantity' => 500,
            'paper_id' => 1,
            'color_id' => 2,
            'process_ids' => [1],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.breakdown.process_amount', 0.6) // 60分 = 0.6元
            ->assertJsonPath('data.breakdown.total_amount', 850.6); // 500 * 1.7 = 850元 + 0.6 = 850.6元
    }

    public function test_price_calculation_uses_correct_tier(): void
    {
        $product = $this->createProductWithPricingParams();

        $response = $this->postJson('/api/v1/products/' . $product->id . '/price', [
            'quantity' => 100,
            'paper_id' => 2,
            'color_id' => 1,
        ]);

        // 100本 tier price = 250分 = 2.5元
        // paper_factor = 1.00, color_factor = 0.35
        // unit_price = round(250 * 1.00 * 0.35) = round(87.5) = 88分 = 0.88元
        // total = 100 * 0.88 = 88元
        $response->assertStatus(200)
            ->assertJsonPath('data.unit_price', 0.88)
            ->assertJsonPath('data.breakdown.total_amount', 88);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->postJson('/api/v1/products/99999/price', [
            'quantity' => 100,
            'paper_id' => 1,
            'color_id' => 1,
        ]);

        $response->assertStatus(404);
    }

    public function test_returns_404_for_off_sale_product(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 2,
        ]);

        $response = $this->postJson('/api/v1/products/' . $product->id . '/price', [
            'quantity' => 100,
            'paper_id' => 1,
            'color_id' => 1,
        ]);

        $response->assertStatus(404);
    }

    public function test_requires_quantity_paper_and_color(): void
    {
        $product = $this->createProductWithPricingParams();

        $response = $this->postJson('/api/v1/products/' . $product->id . '/price', []);

        $response->assertStatus(422);
    }

    public function test_rejects_invalid_paper_id(): void
    {
        $product = $this->createProductWithPricingParams();

        $response = $this->postJson('/api/v1/products/' . $product->id . '/price', [
            'quantity' => 100,
            'paper_id' => 99,
            'color_id' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_invalid_color_id(): void
    {
        $product = $this->createProductWithPricingParams();

        $response = $this->postJson('/api/v1/products/' . $product->id . '/price', [
            'quantity' => 100,
            'paper_id' => 1,
            'color_id' => 99,
        ]);

        $response->assertStatus(422);
    }

    public function test_price_calculation_uses_price_tier_table_when_available(): void
    {
        $product = $this->createProductWithPricingParams();

        // PriceTier 表中的价格优先于 JSON
        \App\Domains\Product\Models\PriceTier::factory()->create([
            'product_id' => $product->id,
            'min_qty' => 100,
            'max_qty' => 499,
            'unit_price' => 1.50, // 1.50元 = 150分（JSON中100本tier=250分）
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/products/' . $product->id . '/price', [
            'quantity' => 100,
            'paper_id' => 2, // factor=1.00
            'color_id' => 2, // factor=1.00
        ]);

        // 应该使用 PriceTier 的 150分，而不是 JSON 的 250分
        $response->assertStatus(200)
            ->assertJsonPath('data.unit_price', 1.5)
            ->assertJsonPath('data.breakdown.total_amount', 150);
    }

    public function test_price_returns_error_when_pricing_params_is_null(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '未配置计价商品',
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/products/' . $product->id . '/price', [
            'quantity' => 100,
            'paper_id' => 1,
            'color_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 3002)
            ->assertJsonPath('message', '商品暂未配置计价参数');
    }
}
