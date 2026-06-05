<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domains\Admin\Models\Admin;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductPricingParamsTest extends TestCase
{
    use RefreshDatabase;

    private function authAdmin(): Admin
    {
        $admin = Admin::factory()->create(['status' => 1]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_admin_can_update_product_pricing_params(): void
    {
        $this->authAdmin();
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $pricingParams = [
            'base_price' => 250,
            'unit' => '本',
            'price_tiers' => [
                ['min_qty' => 100, 'price' => 250],
                ['min_qty' => 500, 'price' => 200],
            ],
            'paper_options' => [
                ['id' => 1, 'name' => '128g铜版纸', 'price_factor' => 0.85],
            ],
            'color_options' => [
                ['id' => 1, 'name' => '单色', 'price_factor' => 0.35],
            ],
            'process_options' => [
                ['id' => 1, 'name' => '覆膜', 'price' => 60, 'unit' => '㎡'],
            ],
        ];

        $response = $this->putJson("/api/v1/admin/products/{$product->id}", [
            'pricing_params' => $pricingParams,
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 0);

        $product->refresh();
        $this->assertEquals($pricingParams, $product->pricing_params);
    }

    public function test_admin_can_clear_product_pricing_params(): void
    {
        $this->authAdmin();
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'pricing_params' => ['base_price' => 100],
        ]);

        $response = $this->putJson("/api/v1/admin/products/{$product->id}", [
            'pricing_params' => null,
        ]);

        $response->assertOk();

        $product->refresh();
        $this->assertNull($product->pricing_params);
    }

    public function test_admin_cannot_update_nonexistent_product(): void
    {
        $this->authAdmin();

        $response = $this->putJson('/api/v1/admin/products/99999', [
            'pricing_params' => ['base_price' => 100],
        ]);

        $response->assertNotFound();
    }

    public function test_pricing_params_must_be_array_or_null(): void
    {
        $this->authAdmin();
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->putJson("/api/v1/admin/products/{$product->id}", [
            'pricing_params' => 'not-an-array',
        ]);

        $response->assertStatus(422);
    }
}
