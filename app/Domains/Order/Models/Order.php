<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use App\Domains\User\Models\Customer;
use App\Events\OrderStatusChanged;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends BaseModel
{
    protected static function booted(): void
    {
        static::updating(function (Order $order): void {
            $originalStatus = (int) $order->getOriginal('status');
            $newStatus = (int) $order->status;

            if ($originalStatus !== $newStatus) {
                OrderStatusChanged::dispatch($order, $originalStatus, $newStatus);
            }
        });
    }
    protected $fillable = [
        'order_no',
        'customer_id',
        'status',
        'out_status_name',
        'total_amount',
        'deposit_sum',
        'discount_sum',
        'customer_coupon_id',
        'express_company',
        'delivery_type',
        'source',
        'remark',
        'paid_at',
        'submitted_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'status' => 'integer',
        'total_amount' => Money::class,
        'deposit_sum' => Money::class,
        'discount_sum' => Money::class,
        'delivery_type' => 'integer',
        'source' => 'integer',
        'customer_coupon_id' => 'integer',
        'paid_at' => 'datetime:Y-m-d H:i:s',
        'submitted_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
