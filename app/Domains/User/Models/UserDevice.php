<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends BaseModel
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'platform',
        'ip_address',
        'last_active_at',
        'is_current',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'is_current' => 'integer',
        'last_active_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }
}
