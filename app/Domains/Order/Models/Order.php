<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use App\Domains\User\Models\Customer;
use App\Domains\Order\StateMachines\OrderStateMachine;
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
        'address_id',
        'receiver_name',
        'receiver_phone',
        'province',
        'city',
        'county',
        'detail_address',
        'status',
        'out_status_name',
        'total_amount',
        'deposit_sum',
        'discount_sum',
        'freight_amount',
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
        'address_id' => 'integer',
        'status' => 'integer',
        'total_amount' => Money::class,
        'deposit_sum' => Money::class,
        'discount_sum' => Money::class,
        'freight_amount' => Money::class,
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

    public function productionSchedules(): HasMany
    {
        return $this->hasMany(ProductionSchedule::class);
    }

    public function orderFiles(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    public function inkCoverageChecks(): HasMany
    {
        return $this->hasMany(InkCoverageCheck::class);
    }

    public function refundRecords(): HasMany
    {
        return $this->hasMany(\App\Domains\Payment\Models\RefundRecord::class);
    }

    public function orderDeliveries(): HasMany
    {
        return $this->hasMany(\App\Domains\Logistics\Models\OrderDelivery::class);
    }

    public function stateMachine(): OrderStateMachine
    {
        return new OrderStateMachine();
    }
}
