<?php

declare(strict_types=1);

namespace App\Domains\AfterSale\Models;

use App\Domains\AfterSale\StateMachines\AfterSaleStateMachine;
use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AfterSale extends BaseModel
{
    protected $table = 'after_sales';

    protected $fillable = [
        'after_sale_no',
        'order_no',
        'customer_id',
        'type',
        'status',
        'reason',
        'description',
        'images',
        'refund_amount',
        'approved_amount',
        'audit_remark',
        'completed_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'images' => 'array',
        'refund_amount' => Money::class,
        'approved_amount' => Money::class,
        'completed_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\User\Models\Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AfterSaleItem::class);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [1, 2], true);
    }

    public function stateMachine(): AfterSaleStateMachine
    {
        return new AfterSaleStateMachine();
    }
}
