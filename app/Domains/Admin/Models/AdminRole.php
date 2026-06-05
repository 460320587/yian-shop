<?php

declare(strict_types=1);

namespace App\Domains\Admin\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminRole extends BaseModel
{
    protected $table = 'admin_roles';

    protected $fillable = ['name', 'code', 'description', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminPermission::class,
            'admin_role_permission',
            'role_id',
            'permission_id'
        )->withPivot('data_scope')->withTimestamps();
    }
}
