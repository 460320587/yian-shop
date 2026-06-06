<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Product\Services;

use App\Domains\Product\Models\PriceTier;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductCategory;
use App\Domains\Product\Services\PricingCalculator;
use App\Exceptions\BusinessException;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private PricingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PricingCalculator();
    }

    private function createProductWithPricingParams(?array $pricingParams = null, bool $useDefault = true): Product
    {
        $category = ProductCategory::factory()->create();

        $defaultParams = [
            'base_price' => 250,
            'unit' => '本',
            'price_tiers' => [
                ['min_qty' => 100, 'price' => 250],
                ['min_qty' => 500, 'price' => 200],
                ['min_qty' => 1000, 'price' => 160],
            ],
            'paper_options' => [
                ['id' => 1, 'name' => '128g铜版纸', 'price_factor' => 0.85],
                ['id' => 2, 'name' => '157g铜版纸', 'price_factor' => 1.00],
            ],
            'color_options' => [
                ['id' => 1, 'name' => '单色', 'price_factor' => 0.35],
                ['id' => 2, 'name' => '四色', 'price_factor' => 1.00],
            ],
            'process_options' => [
                ['id' => 1, 'name' => '覆膜', 'price' => 60, 'unit' => '㎡'],
                ['id' => 2, 'name' => '烫金', 'price' => 200, 'unit' => '㎡'],
            ],
        ];

        $data = [
            'category_id' => $category->id,
            'name' => '宣传册',
            'status' => 1,
        ];

        if ($pricingParams !== null || ! $useDefault) {
            $data['pricing_params'] = $pricingParams;
        } else {
            $data['pricing_params'] = $defaultParams;
        }

        return Product::factory()->create($data);
    }

    public function test_calculates_price_with_basic_params(): void
    {
        $product = $this->createProductWithPricingParams();

        $result = $this->calculator->calculate($product, [
            'quantity' => 1000,
            'paper_id' => 2,
            'color_id' => 2,
        ]);

        // 1000本 tier price = 160分 = 1.6元
        // paper_factor = 1.00, color_factor = 1.00
        // unit_price = 160分 = 1.6元
        // total = 1000 * 1.6 = 1600元
        $this->assertEquals(160, $result->unitPrice->amount); // 160分
        $this->assertEquals(160000, $result->totalAmount->amount); // 160000分 = 1600元
        $this->assertEquals(160000, $result->breakdown['base_amount']);
        $this->assertEquals(0, $result->breakdown['process_amount']);
        $this->assertEquals(160000, $result->breakdown['total_amount']);
    }

    public function test_calculates_price_with_paper_and_color_factors(): void
    {
        $product = $this->createProductWithPricingParams();

        $result = $this->calculator->calculate($product, [
            'quantity' => 100,
            'paper_id' => 1, // 128g铜版纸 factor=0.85
            'color_id' => 1, // 单色 factor=0.35
        ]);

        // 100本 tier price = 250分 = 2.5元
        // unit_price = round(250 * 0.85 * 0.35) = round(74.375) = 74分 = 0.74元
        // total = 100 * 0.74 = 74元 = 7400分
        $this->assertEquals(74, $result->unitPrice->amount);
        $this->assertEquals(7400, $result->totalAmount->amount);
    }

    public function test_calculates_price_with_process_options(): void
    {
        $product = $this->createProductWithPricingParams();

        $result = $this->calculator->calculate($product, [
            'quantity' => 500,
            'paper_id' => 1, // factor=0.85
            'color_id' => 2, // factor=1.00
            'process_ids' => [1, 2], // 覆膜60分 + 烫金200分 = 260分
        ]);

        // 500本 tier price = 200分 = 2.0元
        // unit_price = round(200 * 0.85 * 1.00) = 170分 = 1.7元
        // base_amount = 500 * 1.7 = 850元 = 85000分
        // process_amount = 60 + 200 = 260分 = 2.6元
        // total = 85000 + 260 = 85260分 = 852.6元
        $this->assertEquals(170, $result->unitPrice->amount);
        $this->assertEquals(85000, $result->breakdown['base_amount']);
        $this->assertEquals(260, $result->breakdown['process_amount']);
        $this->assertEquals(85260, $result->breakdown['total_amount']);
    }

    public function test_uses_price_tier_table_when_available(): void
    {
        $product = $this->createProductWithPricingParams([
            'base_price' => 250,
            'unit' => '本',
            'price_tiers' => [
                ['min_qty' => 100, 'price' => 250], // JSON 中的价格
            ],
            'paper_options' => [
                ['id' => 1, 'name' => '128g铜版纸', 'price_factor' => 1.00],
            ],
            'color_options' => [
                ['id' => 1, 'name' => '单色', 'price_factor' => 1.00],
            ],
            'process_options' => [],
        ]);

        // 在 price_tiers 表中创建更精确的价格阶梯（优先于 JSON）
        // unit_price 是 decimal(12,4)，单位为 元，所以 1.80 元 = 180 分
        PriceTier::factory()->create([
            'product_id' => $product->id,
            'min_qty' => 1,
            'max_qty' => 99,
            'unit_price' => 3.00, // 3.00元 = 300分
            'status' => 1,
        ]);
        PriceTier::factory()->create([
            'product_id' => $product->id,
            'min_qty' => 100,
            'max_qty' => 499,
            'unit_price' => 1.80, // 1.80元 = 180分（与JSON中的250不同）
            'status' => 1,
        ]);

        $result = $this->calculator->calculate($product, [
            'quantity' => 100,
            'paper_id' => 1,
            'color_id' => 1,
        ]);

        // 应该使用 PriceTier 表的 180分，而不是 JSON 的 250分
        $this->assertEquals(180, $result->unitPrice->amount);
        $this->assertEquals(18000, $result->totalAmount->amount);
    }

    public function test_falls_back_to_json_price_tiers_when_no_table_records(): void
    {
        $product = $this->createProductWithPricingParams();

        $result = $this->calculator->calculate($product, [
            'quantity' => 100,
            'paper_id' => 2,
            'color_id' => 2,
        ]);

        // 没有 PriceTier 表记录，使用 JSON 的 price_tiers
        // 100本 tier price = 250分
        $this->assertEquals(250, $result->unitPrice->amount);
        $this->assertEquals(25000, $result->totalAmount->amount);
    }

    public function test_uses_base_price_when_no_tiers_available(): void
    {
        $product = $this->createProductWithPricingParams([
            'base_price' => 500,
            'unit' => '张',
            'paper_options' => [
                ['id' => 1, 'name' => '普通纸', 'price_factor' => 1.00],
            ],
            'color_options' => [
                ['id' => 1, 'name' => '黑白', 'price_factor' => 1.00],
            ],
            'process_options' => [],
        ]);

        $result = $this->calculator->calculate($product, [
            'quantity' => 50,
            'paper_id' => 1,
            'color_id' => 1,
        ]);

        // 没有 price_tiers（JSON和表都没有），使用 base_price = 500分
        $this->assertEquals(500, $result->unitPrice->amount);
        $this->assertEquals(25000, $result->totalAmount->amount); // 50 * 500分 = 25000分
    }

    public function test_throws_exception_for_invalid_paper_id(): void
    {
        $product = $this->createProductWithPricingParams();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('无效的参数选项');

        $this->calculator->calculate($product, [
            'quantity' => 100,
            'paper_id' => 99, // 不存在的 paper_id
            'color_id' => 1,
        ]);
    }

    public function test_throws_exception_for_invalid_color_id(): void
    {
        $product = $this->createProductWithPricingParams();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('无效的参数选项');

        $this->calculator->calculate($product, [
            'quantity' => 100,
            'paper_id' => 1,
            'color_id' => 99, // 不存在的 color_id
        ]);
    }

    public function test_throws_exception_for_invalid_process_id(): void
    {
        $product = $this->createProductWithPricingParams();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('无效的参数选项');

        $this->calculator->calculate($product, [
            'quantity' => 100,
            'paper_id' => 1,
            'color_id' => 1,
            'process_ids' => [99], // 不存在的 process_id
        ]);
    }

    public function test_throws_exception_when_pricing_params_is_null(): void
    {
        $product = $this->createProductWithPricingParams(null, false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('商品暂未配置计价参数');

        $this->calculator->calculate($product, [
            'quantity' => 100,
            'paper_id' => 1,
            'color_id' => 1,
        ]);
    }

    public function test_calculates_with_single_process_option(): void
    {
        $product = $this->createProductWithPricingParams();

        $result = $this->calculator->calculate($product, [
            'quantity' => 500,
            'paper_id' => 2,
            'color_id' => 2,
            'process_ids' => [1], // 仅覆膜
        ]);

        // 500本 tier price = 200分
        // unit_price = 200 * 1.00 * 1.00 = 200分
        // base = 500 * 2.0 = 1000元 = 100000分
        // process = 60分
        // total = 100060分 = 1000.6元
        $this->assertEquals(200, $result->unitPrice->amount);
        $this->assertEquals(100000, $result->breakdown['base_amount']);
        $this->assertEquals(60, $result->breakdown['process_amount']);
        $this->assertEquals(100060, $result->breakdown['total_amount']);
    }
}
