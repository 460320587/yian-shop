<?php

declare(strict_types=1);

namespace App\Domains\Enterprise\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBrand extends BaseModel
{
    protected $fillable = [
        'customer_id',
        'name',
        'type',
        'status',
        'entruster',
        'valid_type',
        'valid_start',
        'valid_end',
        'attachment',
        'reject_reason',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'valid_type' => 'integer',
        'valid_start' => 'date:Y-m-d',
        'valid_end' => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
