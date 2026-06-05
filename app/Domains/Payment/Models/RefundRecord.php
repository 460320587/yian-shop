<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Admin\Models\Admin;
use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRecord extends BaseModel
{
    protected $table = 'refund_records';

    protected $fillable = [
        'order_id',
        'payment_id',
        'customer_id',
        'refund_no',
        'amount',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'refund_path',
        'gateway_refund_no',
        'completed_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'payment_id' => 'integer',
        'customer_id' => 'integer',
        'amount' => Money::class,
        'status' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime:Y-m-d H:i:s',
        'completed_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 0;
    }

    public function isApproved(): bool
    {
        return $this->status === 1;
    }

    public function isRejected(): bool
    {
        return $this->status === 2;
    }

    public function isProcessing(): bool
    {
        return $this->status === 3;
    }

    public function isCompleted(): bool
    {
        return $this->status === 4;
    }
}
