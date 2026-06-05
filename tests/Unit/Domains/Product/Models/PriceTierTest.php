<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Product\Models;

use App\Domains\Product\Models\PriceTier;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_price_tier(): void
    {
        $tier = PriceTier::factory()->create();
        $this->assertDatabaseHas('price_tiers', ['id' => $tier->id]);
    }

    public function test_tier_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $tier = PriceTier::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $tier->product);
    }

    public function test_min_qty_and_max_qty_are_integers(): void
    {
        $tier = PriceTier::factory()->create(['min_qty' => 10, 'max_qty' => 100]);
        $this->assertSame(10, $tier->min_qty);
        $this->assertSame(100, $tier->max_qty);
    }

    public function test_unit_price_has_four_decimals(): void
    {
        $tier = PriceTier::factory()->create(['unit_price' => 12.3456]);
        $this->assertSame('12.3456', (string) $tier->unit_price);
    }
}
