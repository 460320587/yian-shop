<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductParamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_product_pricing_params(): void
    {
        $category = ProductCategory::factory()->create();
        $pricingParams = [
            'base_price' => 250,
            'unit' => '本',
            'price_tiers' => [
                ['min_qty' => 100, 'price' => 250],
                ['min_qty' => 500, 'price' => 200],
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
            ],
        ];
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '宣传册',
            'status' => 1,
            'pricing_params' => $pricingParams,
        ]);

        $response = $this->getJson('/api/v1/products/' . $product->id . '/params');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    'product_id',
                    'pricing_params' => [
                        'base_price',
                        'unit',
                        'price_tiers',
                        'paper_options',
                        'color_options',
                        'process_options',
                    ],
                ],
            ])
            ->assertJsonPath('data.pricing_params.base_price', 250)
            ->assertJsonPath('data.pricing_params.unit', '本')
            ->assertJsonCount(2, 'data.pricing_params.paper_options')
            ->assertJsonCount(2, 'data.pricing_params.color_options')
            ->assertJsonCount(1, 'data.pricing_params.process_options');
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/products/99999/params');
        $response->assertStatus(404);
    }

    public function test_returns_404_for_off_sale_product(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 2, // 下架
        ]);

        $response = $this->getJson('/api/v1/products/' . $product->id . '/params');
        $response->assertStatus(404);
    }
}
