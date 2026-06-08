<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Domains\Logistics\Models\FreightTemplate;

class FreightCalculator
{
    /**
     * 计算运费（返回金额，单位：分）
     */
    public static function calculate(int $goodsAmountCents, int $totalQuantity): int
    {
        $template = FreightTemplate::where('status', 1)->first();

        if (! $template) {
            return 0;
        }

        $freeThreshold = $template->free_threshold;

        if ($freeThreshold !== null) {
            $thresholdCents = (int) round((float) $freeThreshold * 100);
            if ($goodsAmountCents >= $thresholdCents) {
                return 0;
            }
        }

        $firstPrice = (float) $template->first_price;
        $continuePrice = (float) $template->continue_price;
        $extraQty = max(0, $totalQuantity - 1);

        return (int) round(($firstPrice + $extraQty * $continuePrice) * 100);
    }
}
