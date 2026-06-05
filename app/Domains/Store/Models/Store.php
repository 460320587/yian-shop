<?php

declare(strict_types=1);

namespace App\Domains\Store\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends BaseModel
{
    protected $table = 'stores';

    protected $fillable = [
        'store_code',
        'store_name',
        'store_type',
        'province',
        'city',
        'district',
        'address',
        'longitude',
        'latitude',
        'contact_phone',
        'manager_id',
        'manager_name',
        'coverage_area',
        'business_hours',
        'capacity_daily',
        'current_load',
        'status',
        'support_pickup',
        'support_delivery',
        'delivery_range',
        'factory_id',
    ];

    protected $casts = [
        'store_type' => 'integer',
        'status' => 'integer',
        'capacity_daily' => 'integer',
        'current_load' => 'integer',
        'delivery_range' => 'integer',
        'longitude' => 'decimal:7',
        'latitude' => 'decimal:7',
        'support_pickup' => 'boolean',
        'support_delivery' => 'boolean',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'manager_id');
    }
}
