<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionSchedule extends BaseModel
{
    protected $table = 'production_schedules';

    protected $fillable = [
        'order_id',
        'factory_id',
        'schedule_date',
        'start_time',
        'end_time',
        'process_name',
        'equipment_id',
        'operator_id',
        'status',
        'priority',
        'estimated_hours',
        'actual_hours',
        'progress',
        'delay_reason',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'factory_id' => 'integer',
        'equipment_id' => 'integer',
        'operator_id' => 'integer',
        'status' => 'integer',
        'priority' => 'integer',
        'progress' => 'integer',
        'estimated_hours' => 'float',
        'actual_hours' => 'float',
        'schedule_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
