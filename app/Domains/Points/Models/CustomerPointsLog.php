<?php

declare(strict_types=1);

namespace App\Domains\Points\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPointsLog extends BaseModel
{
    protected $table = 'customer_points_logs';

    protected $fillable = [
        'customer_id',
        'type',
        'points',
        'balance_before',
        'balance_after',
        'order_no',
        'remark',
        'expired_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'type' => 'integer',
        'points' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'expired_at' => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\User\Models\Customer::class);
    }
}
