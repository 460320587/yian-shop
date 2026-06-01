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
        'zip_code',
        'contact_name',
        'contact_phone',
        'is_default',
        'tag',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'is_default' => 'boolean',
        'zip_code' => 'string',
        'tag' => 'string',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
