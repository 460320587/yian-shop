<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Domains\Common\Models\BaseModel;

class Carrier extends BaseModel
{
    protected $fillable = [
        'name',
        'code',
        'api_type',
        'config',
        'is_default',
        'status',
    ];

    protected $casts = [
        'config' => 'array',
        'is_default' => 'integer',
        'status' => 'integer',
    ];
}
