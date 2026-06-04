<?php

declare(strict_types=1);

namespace App\Domains\Coupon\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerCoupon extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'coupon_id', 'code', 'status',
        'claimed_at', 'used_at', 'expired_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'coupon_id' => 'integer',
        'status' => 'integer',
        'claimed_at' => 'datetime',
        'used_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopeUnused($query)
    {
        return $query->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            });
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
