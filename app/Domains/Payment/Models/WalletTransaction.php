<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends BaseModel
{
    protected $table = 'wallet_transactions';

    protected $fillable = [
        'customer_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'order_no',
        'payment_no',
        'remark',
        'status',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'type' => 'integer',
        'balance_before' => Money::class,
        'balance_after' => Money::class,
        'status' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeOfCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeOfType($query, int $type)
    {
        return $query->where('type', $type);
    }

    public function isIncome(): bool
    {
        return $this->amount->amount > 0;
    }
}
