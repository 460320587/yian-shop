<?php

declare(strict_types=1);

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_key',
        'config_value',
        'type',
        'description',
        'group',
    ];
}
