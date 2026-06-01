<?php

declare(strict_types=1);

namespace App\Domains\Notification\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNotification extends BaseModel
{
    protected $table = 'customer_notifications';

    protected $fillable = [
        'customer_id',
        'type',
        'title',
        'content',
        'is_read',
        'action_url',
        'action_text',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'is_read' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
