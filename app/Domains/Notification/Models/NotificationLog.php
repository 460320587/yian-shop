<?php

declare(strict_types=1);

namespace App\Domains\Notification\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends BaseModel
{
    protected $table = 'notification_logs';

    protected $fillable = [
        'customer_id',
        'template_code',
        'channel',
        'type',
        'recipient',
        'title',
        'content',
        'variables',
        'status',
        'sent_at',
        'read_at',
        'response',
        'error_msg',
        'retry_count',
        'dedup_key',
        'failover_from',
        'aggregated_id',
        'biz_id',
        'biz_type',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'channel' => 'integer',
        'status' => 'integer',
        'retry_count' => 'integer',
        'aggregated_id' => 'integer',
        'variables' => 'array',
        'response' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
