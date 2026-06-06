<?php

declare(strict_types=1);

namespace App\Domains\Audit\Models;

use App\Domains\Common\Models\BaseModel;

class AuditArchive extends BaseModel
{
    protected $table = 'audit_archives';

    protected $fillable = [
        'archive_date',
        'storage_path',
        'format',
        'record_count',
        'expire_date',
        'status',
    ];

    protected $casts = [
        'record_count' => 'integer',
        'status' => 'integer',
        'archive_date' => 'date',
        'expire_date' => 'date',
    ];
}
