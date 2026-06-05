<?php

declare(strict_types=1);

namespace App\Domains\Product\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceTier extends BaseModel
{
    protected $table = 'price_tiers';

    protected $fillable = [
        'product_id',
        'min_qty',
        'max_qty',
        'unit_price',
        'status',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'min_qty' => 'integer',
        'max_qty' => 'integer',
        'unit_price' => 'decimal:4',
        'status' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
