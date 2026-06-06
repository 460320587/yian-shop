<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends BaseModel
{
    protected $fillable = [
        'user_id',
        'phone',
        'type',
        'status',
        'fail_reason',
        'ip_address',
        'user_agent',
        'device_id',
        'location',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }
}
