<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Domains\Admin\Models\Admin;
use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusLog extends BaseModel
{
    protected $table = 'order_status_logs';

    protected $fillable = [
        'order_id', 'from_status', 'to_status',
        'remark', 'operator_id', 'operator_type',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'from_status' => 'integer',
        'to_status' => 'integer',
        'operator_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'operator_id');
    }
}
