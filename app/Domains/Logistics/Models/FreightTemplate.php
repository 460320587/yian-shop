<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreightTemplate extends BaseModel
{
    protected $fillable = [
        'name',
        'carrier_id',
        'calculation_type',
        'first_weight',
        'first_price',
        'continue_weight',
        'continue_price',
        'free_threshold',
        'regions',
        'status',
    ];

    protected $casts = [
        'carrier_id' => 'integer',
        'calculation_type' => 'integer',
        'first_weight' => 'decimal:3',
        'first_price' => 'decimal:2',
        'continue_weight' => 'decimal:3',
        'continue_price' => 'decimal:2',
        'free_threshold' => 'decimal:2',
        'regions' => 'array',
        'status' => 'integer',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }
}
