<?php

declare(strict_types=1);

namespace App\Domains\Notification\Models;

use App\Domains\Common\Models\BaseModel;

class SmsLog extends BaseModel
{
    protected $fillable = [
        'phone',
        'template_code',
        'content',
        'type',
        'status',
        'provider',
        'error_msg',
        'ip_address',
        'request_id',
    ];

    protected $casts = [
        'type' => 'integer',
        'status' => 'integer',
    ];
}
