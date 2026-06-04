<?php

declare(strict_types=1);

namespace App\Domains\Coupon\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value',
        'min_amount', 'max_discount', 'start_at', 'end_at',
        'total_count', 'per_customer_limit',
        'claimed_count', 'used_count', 'status',
    ];

    protected $casts = [
        'type' => 'integer',
        'value' => 'integer',
        'min_amount' => Money::class,
        'max_discount' => Money::class,
        'total_count' => 'integer',
        'per_customer_limit' => 'integer',
        'claimed_count' => 'integer',
        'used_count' => 'integer',
        'status' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function customerCoupons(): HasMany
    {
        return $this->hasMany(CustomerCoupon::class);
    }

    public function scopeAvailable($query)
    {
        $now = now();
        return $query
            ->where('status', 1)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->where(function ($q) {
                $q->where('total_count', -1)
                    ->orWhereColumn('claimed_count', '<', 'total_count');
            });
    }

    public function isExhausted(): bool
    {
        return $this->total_count !== -1 && $this->claimed_count >= $this->total_count;
    }

    public function isExpired(): bool
    {
        $now = now();
        return $now < $this->start_at || $now > $this->end_at;
    }

    public function isActive(): bool
    {
        return $this->status === 1 && ! $this->isExpired();
    }
}
