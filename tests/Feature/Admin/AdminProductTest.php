<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_products(): void
    {
        $this->authAdmin();
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'code', 'price_min', 'price_max', 'status', 'category'],
                    ],
                ],
            ]);
        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_admin_can_search_products(): void
    {
        $this->authAdmin();
        Product::factory()->create(['name' => '名片印刷']);
        Product::factory()->create(['name' => '海报制作']);

        $response = $this->getJson('/api/v1/admin/products?keyword=名片');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('名片印刷', $response->json('data.data.0.name'));
    }

    public function test_admin_can_view_product_detail(): void
    {
        $this->authAdmin();
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/admin/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_admin_can_create_product(): void
    {
        $this->authAdmin();
        $category = ProductCategory::factory()->create();

        $response = $this->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name' => '新产品',
            'code' => 'NEW-001',
            'price_min' => 1000,
            'price_max' => 5000,
            'status' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', '新产品');
        $this->assertDatabaseHas('products', ['name' => '新产品', 'code' => 'NEW-001']);
    }

    public function test_admin_can_update_product(): void
    {
        $this->authAdmin();
        $product = Product::factory()->create();

        $response = $this->putJson("/api/v1/admin/products/{$product->id}", [
            'name' => '更新后的名称',
            'price_min' => 2000,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => '更新后的名称']);
    }

    public function test_admin_can_toggle_product_status(): void
    {
        $this->authAdmin();
        $product = Product::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/products/{$product->id}/toggle-status");

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 0]);
    }

    public function test_unauthenticated_cannot_access_products(): void
    {
        $this->getJson('/api/v1/admin/products')
            ->assertUnauthorized();
    }
}
