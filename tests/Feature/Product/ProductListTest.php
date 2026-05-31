<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_get_product_list(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id, 'status' => 1]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'cover_image',
                        'price_min',
                        'price_max',
                        'status',
                        'category' => ['id', 'name'],
                    ],
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
            ])
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_list_only_includes_on_sale_products(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'name' => '上架商品', 'status' => 1]);
        Product::factory()->create(['category_id' => $category->id, 'name' => '草稿商品', 'status' => 0]);
        Product::factory()->create(['category_id' => $category->id, 'name' => '下架商品', 'status' => 2]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', '上架商品');
    }

    public function test_list_can_filter_by_category_id(): void
    {
        $catA = ProductCategory::factory()->create();
        $catB = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $catA->id, 'name' => 'A商品', 'status' => 1]);
        Product::factory()->create(['category_id' => $catB->id, 'name' => 'B商品', 'status' => 1]);

        $response = $this->getJson('/api/v1/products?category_id=' . $catA->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'A商品');
    }

    public function test_list_can_search_by_keyword(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'name' => '企业宣传册', 'status' => 1]);
        Product::factory()->create(['category_id' => $category->id, 'name' => '名片印刷', 'status' => 1]);

        $response = $this->getJson('/api/v1/products?keyword=宣传册');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', '企业宣传册');
    }

    public function test_list_can_filter_by_price_range(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'name' => '低价商品', 'price_min' => 1000, 'price_max' => 5000, 'status' => 1]);
        Product::factory()->create(['category_id' => $category->id, 'name' => '中价商品', 'price_min' => 50000, 'price_max' => 100000, 'status' => 1]);
        Product::factory()->create(['category_id' => $category->id, 'name' => '高价商品', 'price_min' => 200000, 'price_max' => 500000, 'status' => 1]);

        $response = $this->getJson('/api/v1/products?min_price=10&max_price=50');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', '低价商品');
    }

    public function test_list_can_sort_by_price_asc(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'name' => '高价', 'price_min' => 50000, 'status' => 1]);
        Product::factory()->create(['category_id' => $category->id, 'name' => '低价', 'price_min' => 1000, 'status' => 1]);

        $response = $this->getJson('/api/v1/products?sort=price_asc');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.0.name', '低价')
            ->assertJsonPath('data.1.name', '高价');
    }

    public function test_list_can_sort_by_price_desc(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'name' => '低价', 'price_min' => 1000, 'status' => 1]);
        Product::factory()->create(['category_id' => $category->id, 'name' => '高价', 'price_min' => 50000, 'status' => 1]);

        $response = $this->getJson('/api/v1/products?sort=price_desc');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.0.name', '高价')
            ->assertJsonPath('data.1.name', '低价');
    }

    public function test_list_can_sort_by_newest(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'name' => '旧商品', 'status' => 1, 'created_at' => now()->subDays(2)]);
        Product::factory()->create(['category_id' => $category->id, 'name' => '新商品', 'status' => 1, 'created_at' => now()]);

        $response = $this->getJson('/api/v1/products?sort=newest');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.0.name', '新商品')
            ->assertJsonPath('data.1.name', '旧商品');
    }

    public function test_list_paginates_correctly(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(25)->create(['category_id' => $category->id, 'status' => 1]);

        $response = $this->getJson('/api/v1/products?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3);
    }
}
