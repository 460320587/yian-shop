<?php

declare(strict_types=1);

namespace App\Domains\Dropship\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformShop extends BaseModel
{
    protected $table = 'platform_shops';

    protected $fillable = [
        'customer_id',
        'platform',
        'shop_name',
        'shop_auth_status',
        'auth_token',
        'expire_time',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'platform' => 'integer',
        'shop_auth_status' => 'integer',
        'expire_time' => 'datetime',
    ];

    protected $hidden = ['auth_token'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
