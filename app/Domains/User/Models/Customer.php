<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function walletRecord(): HasOne
    {
        return $this->hasOne(CustomerWallet::class);
    }

    public function getWalletAttribute(): CustomerWallet
    {
        $wallet = $this->walletRecord;
        if ($wallet === null) {
            $wallet = $this->walletRecord()->create([
                'balance' => 0,
                'frozen_amount' => 0,
                'total_recharge' => 0,
                'total_consume' => 0,
                'status' => 1,
                'version' => 0,
            ]);
            $this->setRelation('walletRecord', $wallet);
        }
        return $wallet;
    }
}
