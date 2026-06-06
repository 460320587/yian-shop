<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Product\Models\ParamTemplate;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminParamTemplateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_admin_can_list_param_templates_by_category(): void
    {
        $category = ProductCategory::factory()->create(['name' => '名片印刷']);
        ParamTemplate::factory()->count(3)->create([
            'category_id' => $category->id,
            'param_name' => '纸张类型',
            'status' => 1,
        ]);
        ParamTemplate::factory()->create([
            'category_id' => $category->id,
            'param_name' => '颜色',
            'status' => 0,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/v1/admin/param-templates?category_id=' . $category->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(4, 'data');
    }

    public function test_admin_can_get_param_template_detail(): void
    {
        $category = ProductCategory::factory()->create();
        $template = ParamTemplate::factory()->create([
            'category_id' => $category->id,
            'param_type' => 'select',
            'param_name' => '纸张类型',
            'options' => [
                ['id' => 1, 'name' => '128g铜版纸'],
                ['id' => 2, 'name' => '157g铜版纸'],
            ],
            'status' => 1,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/v1/admin/param-templates/' . $template->id);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.param_name', '纸张类型')
            ->assertJsonPath('data.param_type', 'select')
            ->assertJsonCount(2, 'data.options');
    }

    public function test_returns_404_for_nonexistent_template(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/v1/admin/param-templates/99999');

        $response->assertStatus(404);
    }

    public function test_list_returns_empty_for_nonexistent_category(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/v1/admin/param-templates?category_id=99999');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
