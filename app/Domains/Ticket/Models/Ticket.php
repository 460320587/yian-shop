<?php

declare(strict_types=1);

namespace App\Domains\Ticket\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends BaseModel
{
    protected $table = 'tickets';

    protected $fillable = [
        'customer_id', 'order_id', 'ticket_no', 'type', 'status', 'priority',
        'title', 'content', 'images', 'expected_resolution',
        'satisfaction', 'remark', 'processed_by', 'processed_at', 'completed_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'priority' => 'integer',
        'images' => 'array',
        'satisfaction' => 'integer',
        'processed_by' => 'integer',
        'processed_at' => 'datetime:Y-m-d H:i:s',
        'completed_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\User\Models\Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Order\Models\Order::class);
    }
}
