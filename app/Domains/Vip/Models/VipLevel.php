<?php

declare(strict_types=1);

namespace App\Domains\Vip\Models;

use Illuminate\Database\Eloquent\Model;

class VipLevel extends Model
{
    protected $table = 'vip_levels';

    protected $fillable = [
        'level',
        'name',
        'min_points',
        'discount',
        'icon',
        'privileges',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_points' => 'integer',
        'discount' => 'float',
        'privileges' => 'array',
    ];
}
