<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseModel
{
    protected $fillable = [
        'phone',
        'password',
        'nickname',
        'avatar',
        'type',
        'auth_status',
        'vip_level',
        'grow_value',
        'balance',
        'status',
        'link_person',
        'qq',
        'register_ip',
        'last_login_at',
    ];

    protected $casts = [
        'type' => 'integer',
        'auth_status' => 'integer',
        'vip_level' => 'integer',
        'grow_value' => 'integer',
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
}
