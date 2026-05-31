<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends BaseModel
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'spec_info',
        'quantity',
        'unit_price',
        'total_price',
        'file_url',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => Money::class,
        'total_price' => Money::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
