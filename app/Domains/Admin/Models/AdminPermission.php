<?php

declare(strict_types=1);

namespace App\Domains\Admin\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminPermission extends BaseModel
{
    protected $table = 'admin_permissions';

    protected $fillable = ['name', 'code', 'group', 'type'];

    protected $casts = [
        'type' => 'integer',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminRole::class,
            'admin_role_permission',
            'permission_id',
            'role_id'
        )->withPivot('data_scope')->withTimestamps();
    }
}
