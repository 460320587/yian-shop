<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Product\Models;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_via_factory(): void
    {
        $category = ProductCategory::factory()->create();

        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);
    }

    /** @test */
    public function it_has_many_products(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->products);
        $this->assertInstanceOf(Product::class, $category->products->first());
    }

    /** @test */
    public function it_has_many_children(): void
    {
        $parent = ProductCategory::factory()->create();
        ProductCategory::factory()->count(2)->create(['parent_id' => $parent->id]);

        $this->assertCount(2, $parent->children);
    }
}
