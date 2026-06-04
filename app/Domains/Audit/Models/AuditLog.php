<?php

declare(strict_types=1);

namespace App\Domains\Audit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'admin_name',
        'action',
        'model_type',
        'model_id',
        'before_data',
        'after_data',
        'ip',
        'user_agent',
        'result',
        'remark',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'result' => 'integer',
    ];
}
