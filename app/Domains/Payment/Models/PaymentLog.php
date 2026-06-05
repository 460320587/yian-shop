<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends BaseModel
{
    protected $table = 'payment_logs';

    protected $fillable = [
        'payment_id', 'payment_no', 'event',
        'from_status', 'to_status', 'amount', 'gateway_response',
    ];

    protected $casts = [
        'payment_id' => 'integer',
        'from_status' => 'integer',
        'to_status' => 'integer',
        'amount' => 'integer',
        'gateway_response' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
