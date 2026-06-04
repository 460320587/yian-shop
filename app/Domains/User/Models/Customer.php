<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Customer extends BaseModel implements AuthenticatableContract
{
    use HasApiTokens, Authenticatable;
    protected $fillable = [
        'phone',
        'password',
        'nickname',
        'avatar',
        'type',
        'auth_status',
        'vip_level',
        'grow_value',
        'points',
        'balance',
        'status',
        'link_person',
        'qq',
        'register_ip',
        'last_login_at',
        'reset_token',
        'reset_token_expires_at',
    ];

    protected $casts = [
        'type' => 'integer',
        'auth_status' => 'integer',
        'vip_level' => 'integer',
        'grow_value' => 'integer',
        'points' => 'integer',
        'balance' => Money::class,
        'status' => 'integer',
        'last_login_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'password',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(\App\Domains\Enterprise\Models\CustomerBrand::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(\App\Domains\Order\Models\Order::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(\App\Domains\Product\Models\CustomerFavorite::class);
    }

    public function pointsLogs(): HasMany
    {
        return $this->hasMany(\App\Domains\Points\Models\CustomerPointsLog::class);
    }
}
