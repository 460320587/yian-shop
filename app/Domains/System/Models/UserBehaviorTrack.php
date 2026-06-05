<?php

declare(strict_types=1);

namespace App\Domains\System\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBehaviorTrack extends BaseModel
{
    protected $table = 'user_behavior_tracks';

    protected $fillable = [
        'customer_id',
        'session_id',
        'event_type',
        'page_path',
        'element_id',
        'element_text',
        'referrer',
        'device_type',
        'browser',
        'os',
        'event_data',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'event_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
