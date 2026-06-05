<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubAccount extends BaseModel
{
    protected $table = 'sub_accounts';

    protected $fillable = [
        'parent_id',
        'username',
        'password_hash',
        'link_person',
        'mobile_phone',
        'email',
        'role',
        'sub_permission',
        'permissions_json',
        'status',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sub_permission' => 'integer',
        'permissions_json' => 'array',
        'status' => 'integer',
    ];

    protected $hidden = ['password_hash'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'parent_id');
    }

    public function hasPermission(int $flag): bool
    {
        if ($this->sub_permission === 0) {
            return true;
        }
        return ($this->sub_permission & $flag) !== 0;
    }

    public function isAdmin(): bool
    {
        return $this->sub_permission === 0;
    }
}
