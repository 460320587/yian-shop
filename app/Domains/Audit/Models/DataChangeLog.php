<?php

declare(strict_types=1);

namespace App\Domains\Audit\Models;

use App\Domains\Common\Models\BaseModel;

class DataChangeLog extends BaseModel
{
    protected $table = 'data_change_logs';

    protected $fillable = [
        'table_name',
        'record_id',
        'action_type',
        'field_name',
        'old_value',
        'new_value',
        'operator_id',
        'operator_name',
        'operator_type',
        'request_id',
        'ip_address',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'action_type' => 'integer',
        'operator_id' => 'integer',
        'operator_type' => 'integer',
    ];
}
