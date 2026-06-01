<?php

declare(strict_types=1);

namespace App\Domains\Sample\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleOrder extends BaseModel
{
    protected $table = 'sample_orders';

    protected $fillable = [
        'customer_id',
        'order_no',
        'product_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'total_amount',
        'status',
        'address_snapshot',
        'remark',
        'paid_at',
        'shipped_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => Money::class,
        'discount_amount' => Money::class,
        'total_amount' => Money::class,
        'status' => 'integer',
        'address_snapshot' => 'array',
        'paid_at' => 'datetime:Y-m-d H:i:s',
        'shipped_at' => 'datetime:Y-m-d H:i:s',
        'completed_at' => 'datetime:Y-m-d H:i:s',
        'cancelled_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\User\Models\Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Product\Models\Product::class);
    }

    public function canCancel(): bool
    {
        return $this->status === 100;
    }
}
