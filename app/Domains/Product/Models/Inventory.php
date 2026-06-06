<?php

declare(strict_types=1);

namespace App\Domains\Product\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends BaseModel
{
    protected $fillable = [
        'product_id',
        'available_qty',
        'reserved_qty',
        'locked_qty',
        'safety_stock',
        'version',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'available_qty' => 'integer',
        'reserved_qty' => 'integer',
        'locked_qty' => 'integer',
        'safety_stock' => 'integer',
        'version' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
