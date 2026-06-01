<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends BaseModel
{
    protected $fillable = [
        'payment_no',
        'order_no',
        'customer_id',
        'gateway',
        'amount',
        'status',
        'transaction_no',
        'credential',
        'paid_at',
        'expire_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'amount' => Money::class,
        'status' => 'integer',
        'credential' => 'array',
        'paid_at' => 'datetime:Y-m-d H:i:s',
        'expire_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
