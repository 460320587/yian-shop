<?php

declare(strict_types=1);

namespace App\Domains\Notification\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationBadge extends BaseModel
{
    protected $table = 'notification_badges';

    protected $fillable = [
        'customer_id',
        'notification_type',
        'unread_count',
        'last_read_time',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'unread_count' => 'integer',
        'last_read_time' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
