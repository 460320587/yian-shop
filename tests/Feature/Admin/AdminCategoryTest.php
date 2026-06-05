<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_list_categories(): void
    {
        $this->authAdmin();
        ProductCategory::factory()->count(3)->create(['parent_id' => 0, 'level' => 1]);

        $response = $this->getJson('/api/v1/admin/categories');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'parent_id', 'icon', 'sort', 'status', 'level', 'path'],
                ],
            ]);
    }

    public function test_admin_can_create_category(): void
    {
        $this->authAdmin();

        $response = $this->postJson('/api/v1/admin/categories', [
            'name' => '新分类',
            'parent_id' => 0,
            'sort' => 10,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.name', '新分类');

        $this->assertDatabaseHas('product_categories', [
            'name' => '新分类',
            'parent_id' => 0,
            'level' => 1,
            'path' => '',
        ]);
    }

    public function test_admin_can_create_sub_category(): void
    {
        $this->authAdmin();
        $parent = ProductCategory::factory()->create(['parent_id' => 0, 'level' => 1]);

        $response = $this->postJson('/api/v1/admin/categories', [
            'name' => '子分类',
            'parent_id' => $parent->id,
            'sort' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.name', '子分类')
            ->assertJsonPath('data.level', 2);

        $this->assertDatabaseHas('product_categories', [
            'name' => '子分类',
            'parent_id' => $parent->id,
            'level' => 2,
            'path' => $parent->id,
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $this->authAdmin();
        $category = ProductCategory::factory()->create(['name' => '旧名称']);

        $response = $this->putJson("/api/v1/admin/categories/{$category->id}", [
            'name' => '新名称',
            'sort' => 99,
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => '新名称',
            'sort' => 99,
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $this->authAdmin();
        $category = ProductCategory::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertSoftDeleted('product_categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_category_with_products(): void
    {
        $this->authAdmin();
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(422)
            ->assertJsonPath('code', 422);

        $this->assertDatabaseHas('product_categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $this->authAdmin();
        $category = ProductCategory::factory()->create(['status' => 1]);

        $response = $this->putJson("/api/v1/admin/categories/{$category->id}/toggle-status");

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.status', 0);
    }

    public function test_unauthenticated_cannot_access_categories(): void
    {
        $this->getJson('/api/v1/admin/categories')->assertUnauthorized();
    }
}
