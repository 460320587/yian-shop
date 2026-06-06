<?php

declare(strict_types=1);

namespace App\Domains\Notification\Models;

use App\Domains\Common\Models\BaseModel;

class NotificationTemplate extends BaseModel
{
    protected $fillable = [
        'code',
        'name',
        'event',
        'channels',
        'sms_template_code',
        'email_subject',
        'email_body',
        'wechat_template_id',
        'in_app_title',
        'in_app_content',
        'status',
    ];

    protected $casts = [
        'channels' => 'array',
        'status' => 'integer',
    ];
}
