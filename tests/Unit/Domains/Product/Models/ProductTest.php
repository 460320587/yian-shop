<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Product\Models;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_via_factory(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    /** @test */
    public function it_belongs_to_category(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(ProductCategory::class, $product->category);
    }

    /** @test */
    public function it_casts_prices_to_money(): void
    {
        $product = Product::factory()->create([
            'price_min' => 1000,
            'price_max' => 50000,
        ]);

        $this->assertInstanceOf(Money::class, $product->price_min);
        $this->assertInstanceOf(Money::class, $product->price_max);
        $this->assertSame(10.0, $product->price_min->toYuan());
        $this->assertSame(500.0, $product->price_max->toYuan());
    }

    /** @test */
    public function it_responds_to_active_scope(): void
    {
        Product::factory()->count(2)->create(['status' => 1]);
        Product::factory()->create(['status' => 0]);

        $this->assertCount(2, Product::active()->get());
    }
}
