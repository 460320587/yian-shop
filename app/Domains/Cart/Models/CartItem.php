<?php

declare(strict_types=1);

namespace App\Domains\Cart\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends BaseModel
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'selected',
        'spec_info',
    ];

    protected $casts = [
        'cart_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'subtotal' => 'integer',
        'selected' => 'integer',
        'spec_info' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
