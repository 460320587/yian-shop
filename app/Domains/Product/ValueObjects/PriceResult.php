<?php

declare(strict_types=1);

namespace App\Domains\Product\ValueObjects;

use App\Domains\Common\ValueObjects\Money;

final readonly class PriceResult
{
    /**
     * @param  array<string, mixed>  $breakdown
     */
    public function __construct(
        public Money $unitPrice,
        public Money $totalAmount,
        public array $breakdown,
    ) {}
}
