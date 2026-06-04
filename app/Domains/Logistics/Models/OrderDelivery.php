<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderDelivery extends BaseModel
{
    protected $table = 'order_deliveries';

    protected $fillable = [
        'order_id',
        'carrier_name',
        'tracking_no',
        'status',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'status' => 'integer',
        'shipped_at' => 'datetime:Y-m-d H:i:s',
        'delivered_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Order\Models\Order::class);
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(ExpressTrack::class, 'delivery_id');
    }
}
