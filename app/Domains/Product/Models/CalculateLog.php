<?php

declare(strict_types=1);

namespace App\Domains\Product\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculateLog extends BaseModel
{
    protected $table = 'calculate_logs';

    protected $fillable = [
        'order_id',
        'product_id',
        'params',
        'formula',
        'result',
        'calculated_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'params' => 'array',
        'result' => 'integer',
        'calculated_at' => 'datetime',
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
