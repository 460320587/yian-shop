<?php

declare(strict_types=1);

namespace App\Events;

use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Product $product,
        public int $oldStatus,
        public int $newStatus,
    ) {
    }
}
