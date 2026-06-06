<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends BaseModel
{
    protected $fillable = [
        'user_id',
        'real_name',
        'gender',
        'birthday',
        'id_card',
        'industry',
        'position',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'gender' => 'integer',
        'birthday' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }
}
