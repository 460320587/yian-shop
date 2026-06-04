<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpressTrack extends Model
{
    use HasFactory;
    protected $table = 'express_tracks';

    protected $fillable = [
        'delivery_id',
        'track_time',
        'location',
        'description',
    ];

    protected $casts = [
        'delivery_id' => 'integer',
        'track_time' => 'datetime:Y-m-d H:i:s',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(OrderDelivery::class, 'delivery_id');
    }
}
