<?php

declare(strict_types=1);

namespace App\Domains\Admin\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use MustVerifyEmail;
    use HasApiTokens;
    use Notifiable;

    protected $fillable = [
        'username', 'password', 'real_name', 'phone', 'email', 'role', 'status',
        'last_login_at', 'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status' => 'integer',
        'last_login_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
