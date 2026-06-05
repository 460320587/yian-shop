<?php

declare(strict_types=1);

namespace App\Events;

use App\Domains\AfterSale\Models\AfterSale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AfterSaleApplied
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AfterSale $afterSale,
    ) {
    }
}
