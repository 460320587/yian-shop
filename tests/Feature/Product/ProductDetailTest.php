<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_get_product_detail(): void
    {
        $category = ProductCategory::factory()->create(['name' => '宣传册']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'A4企业宣传册',
            'code' => 'PROD-001',
            'status' => 1,
        ]);

        $response = $this->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'code',
                    'cover_image',
                    'price_min',
                    'price_max',
                    'status',
                    'sort',
                    'category' => ['id', 'name'],
                ],
            ])
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.name', 'A4企业宣传册')
            ->assertJsonPath('data.category.name', '宣传册');
    }

    public function test_detail_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/products/99999');

        $response->assertStatus(404)
            ->assertJsonPath('code', 3000)
            ->assertJsonPath('message', '商品不存在');
    }

    public function test_detail_returns_404_for_off_sale_product(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '下架商品',
            'status' => 2,
        ]);

        $response = $this->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(404)
            ->assertJsonPath('code', 3000);
    }

    public function test_detail_returns_404_for_deleted_product(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '已删除商品',
            'status' => 1,
        ]);
        $product->delete();

        $response = $this->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(404)
            ->assertJsonPath('code', 3000);
    }

    public function test_detail_includes_pricing_params(): void
    {
        $category = ProductCategory::factory()->create(['name' => '宣传册']);
        $pricingParams = [
            'base_price' => 250,
            'unit' => '本',
            'price_tiers' => [
                ['min_qty' => 100, 'price' => 250],
            ],
            'paper_options' => [
                ['id' => 1, 'name' => '128g铜版纸', 'price_factor' => 0.85],
            ],
            'color_options' => [
                ['id' => 1, 'name' => '单色', 'price_factor' => 0.35],
            ],
            'process_options' => [],
        ];
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'A4企业宣传册',
            'code' => 'PROD-001',
            'status' => 1,
            'pricing_params' => $pricingParams,
        ]);

        $response = $this->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.pricing_params.base_price', 250)
            ->assertJsonPath('data.pricing_params.unit', '本')
            ->assertJsonCount(1, 'data.pricing_params.price_tiers')
            ->assertJsonCount(1, 'data.pricing_params.paper_options')
            ->assertJsonCount(1, 'data.pricing_params.color_options');
    }

    public function test_detail_pricing_params_is_null_when_not_set(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => '未配置计价商品',
            'status' => 1,
        ]);

        $response = $this->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.pricing_params', null);
    }
}
