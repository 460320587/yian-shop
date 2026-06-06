<?php

declare(strict_types=1);

namespace App\Domains\Product\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends BaseModel
{
    protected $fillable = [
        'product_id',
        'order_no',
        'type',
        'qty_before',
        'qty_change',
        'qty_after',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'type' => 'integer',
        'qty_before' => 'integer',
        'qty_change' => 'integer',
        'qty_after' => 'integer',
        'created_by' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
