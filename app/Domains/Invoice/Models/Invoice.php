<?php

declare(strict_types=1);

namespace App\Domains\Invoice\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends BaseModel
{
    protected $table = 'invoices';

    protected $fillable = [
        'order_id', 'customer_id', 'invoice_no', 'type', 'status', 'business_type',
        'title', 'tax_number', 'amount', 'email', 'address',
        'bank_name', 'bank_account', 'express_no', 'issued_at', 'remark',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'customer_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'business_type' => 'integer',
        'amount' => Money::class,
        'issued_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\User\Models\Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Order\Models\Order::class);
    }
}
