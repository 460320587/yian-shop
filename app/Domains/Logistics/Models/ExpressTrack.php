<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpressTrack extends BaseModel
{
    protected $table = 'express_tracks';

    protected $fillable = [
        'delivery_id',
        'track_time',
        'location',
        'latitude',
        'longitude',
        'description',
    ];

    protected $casts = [
        'delivery_id' => 'integer',
        'track_time' => 'datetime:Y-m-d H:i:s',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(OrderDelivery::class, 'delivery_id');
    }
}
