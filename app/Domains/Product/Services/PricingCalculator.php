<?php

declare(strict_types=1);

namespace App\Domains\Product\Services;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Product\Models\PriceTier;
use App\Domains\Product\Models\Product;
use App\Domains\Product\ValueObjects\PriceResult;
use App\Exceptions\BusinessException;
use App\Support\ErrorCode;

class PricingCalculator
{
    public function calculate(Product $product, array $params): PriceResult
    {
        $pricingParams = $product->pricing_params;

        if ($pricingParams === null || empty($pricingParams)) {
            throw new BusinessException(
                ErrorCode::PRODUCT_PRICE_CHANGED,
                '商品暂未配置计价参数'
            );
        }

        $quantity = (int) ($params['quantity'] ?? 1);
        $paperId = (int) ($params['paper_id'] ?? 0);
        $colorId = (int) ($params['color_id'] ?? 0);
        $processIds = (array) ($params['process_ids'] ?? []);

        // 1. 获取阶梯单价（PriceTier 表优先，其次 JSON price_tiers，最后 base_price）
        $tierPrice = $this->resolveTierPrice($product, $quantity, $pricingParams);

        // 2. 获取纸张系数
        $paperFactor = $this->resolveFactor($pricingParams, 'paper_options', $paperId);

        // 3. 获取颜色系数
        $colorFactor = $this->resolveFactor($pricingParams, 'color_options', $colorId);

        // 4. 计算单价（分）
        $unitPriceAmount = (int) round($tierPrice * $paperFactor * $colorFactor);
        $baseAmount = $unitPriceAmount * $quantity;

        // 5. 计算工艺费用
        $processAmount = $this->calculateProcessAmount($pricingParams, $processIds);

        $totalAmount = $baseAmount + $processAmount;

        return new PriceResult(
            unitPrice: new Money($unitPriceAmount),
            totalAmount: new Money($totalAmount),
            breakdown: [
                'base_amount' => $baseAmount,
                'process_amount' => $processAmount,
                'total_amount' => $totalAmount,
            ],
        );
    }

    private function resolveTierPrice(Product $product, int $quantity, array $pricingParams): int
    {
        // 优先从 PriceTier 表查询
        $tableTier = PriceTier::where('product_id', $product->id)
            ->where('status', 1)
            ->where('min_qty', '<=', $quantity)
            ->where(function ($query) use ($quantity): void {
                $query->where('max_qty', '>=', $quantity)
                    ->orWhere('max_qty', 0);
            })
            ->orderBy('min_qty', 'desc')
            ->first();

        if ($tableTier !== null) {
            return (int) round($tableTier->unit_price * 100); // unit_price 是 decimal(12,4)，转为分
        }

        // 其次从 JSON price_tiers 查找
        $priceTiers = $pricingParams['price_tiers'] ?? [];
        if (! empty($priceTiers)) {
            // 按 min_qty 降序排列，找到第一个满足 quantity >= min_qty 的阶梯
            usort($priceTiers, fn (array $a, array $b) => $b['min_qty'] <=> $a['min_qty']);
            foreach ($priceTiers as $tier) {
                if ($quantity >= $tier['min_qty']) {
                    return (int) $tier['price'];
                }
            }
        }

        // 最后回退到 base_price
        return (int) ($pricingParams['base_price'] ?? 0);
    }

    private function resolveFactor(array $pricingParams, string $optionKey, int $selectedId): float
    {
        $options = $pricingParams[$optionKey] ?? [];

        if (empty($options)) {
            // 如果没有配置该选项，factor = 1.0（不影响价格）
            return 1.0;
        }

        foreach ($options as $option) {
            if ((int) $option['id'] === $selectedId) {
                return (float) ($option['price_factor'] ?? 1.0);
            }
        }

        throw new BusinessException(
            ErrorCode::PRODUCT_PRICE_CHANGED,
            '无效的参数选项'
        );
    }

    private function calculateProcessAmount(array $pricingParams, array $processIds): int
    {
        $processOptions = $pricingParams['process_options'] ?? [];

        if (empty($processOptions) || empty($processIds)) {
            return 0;
        }

        $amount = 0;
        foreach ($processIds as $pid) {
            $found = false;
            foreach ($processOptions as $option) {
                if ((int) $option['id'] === (int) $pid) {
                    $amount += (int) ($option['price'] ?? 0);
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                throw new BusinessException(
                    ErrorCode::PRODUCT_PRICE_CHANGED,
                    '无效的参数选项'
                );
            }
        }

        return $amount;
    }
}
