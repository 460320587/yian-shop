<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Product\Models;

use App\Domains\Product\Models\ParamTemplate;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParamTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_param_template(): void
    {
        $template = ParamTemplate::factory()->create();
        $this->assertDatabaseHas('param_templates', ['id' => $template->id]);
    }

    public function test_template_belongs_to_category(): void
    {
        $category = ProductCategory::factory()->create();
        $template = ParamTemplate::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(ProductCategory::class, $template->category);
    }

    public function test_options_is_cast_to_array(): void
    {
        $template = ParamTemplate::factory()->create(['options' => ['a' => 1]]);
        $this->assertIsArray($template->options);
    }

    public function test_rules_is_cast_to_array(): void
    {
        $template = ParamTemplate::factory()->create(['rules' => ['when' => 'x', 'then' => 'y']]);
        $this->assertIsArray($template->rules);
    }
}
