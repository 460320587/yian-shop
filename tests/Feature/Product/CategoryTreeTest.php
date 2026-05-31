<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_get_category_tree(): void
    {
        $parent = ProductCategory::factory()->create(['name' => '商务印刷', 'sort' => 1]);
        ProductCategory::factory()->create([
            'parent_id' => $parent->id,
            'name' => '宣传册',
            'sort' => 1,
            'level' => 2,
            'path' => $parent->id,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'icon',
                        'sort',
                        'children' => [
                            '*' => ['id', 'name', 'sort'],
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.0.name', '商务印刷')
            ->assertJsonPath('data.0.children.0.name', '宣传册');
    }

    public function test_tree_only_includes_active_categories(): void
    {
        $active = ProductCategory::factory()->create(['name' => '启用分类', 'status' => 1]);
        ProductCategory::factory()->create(['name' => '禁用分类', 'status' => 0]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0);

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('启用分类', $names);
        $this->assertNotContains('禁用分类', $names);
    }

    public function test_tree_respects_depth_param(): void
    {
        $parent = ProductCategory::factory()->create(['name' => '一级']);
        ProductCategory::factory()->create([
            'parent_id' => $parent->id,
            'name' => '二级',
            'level' => 2,
            'path' => $parent->id,
        ]);

        $response = $this->getJson('/api/v1/categories?depth=1');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.0.name', '一级')
            ->assertJsonMissingPath('data.0.children');
    }

    public function test_children_are_sorted_by_sort_field(): void
    {
        $parent = ProductCategory::factory()->create();
        ProductCategory::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'B分类',
            'sort' => 2,
            'level' => 2,
            'path' => $parent->id,
        ]);
        ProductCategory::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'A分类',
            'sort' => 1,
            'level' => 2,
            'path' => $parent->id,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.children.0.name', 'A分类')
            ->assertJsonPath('data.0.children.1.name', 'B分类');
    }

    public function test_portal_categories_returns_tree_with_limited_depth(): void
    {
        $parent = ProductCategory::factory()->create(['name' => '印刷']);
        ProductCategory::factory()->create([
            'parent_id' => $parent->id,
            'name' => '子分类',
            'level' => 2,
            'path' => $parent->id,
        ]);

        $response = $this->getJson('/api/v1/portal/categories');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.0.name', '印刷')
            ->assertJsonPath('data.0.children.0.name', '子分类');
    }
}
