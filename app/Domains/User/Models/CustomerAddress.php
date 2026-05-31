<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends BaseModel
{
    protected $fillable = [
        'customer_id',
        'province_name',
        'city_name',
        'county_name',
        'detail_address',
        'contact_name',
        'contact_phone',
        'is_default',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'is_default' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
