<?php

declare(strict_types=1);

namespace App\Domains\AfterSale\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AfterSaleItem extends BaseModel
{
    protected $table = 'after_sale_items';

    protected $fillable = [
        'after_sale_id',
        'order_item_id',
        'product_name',
        'quantity',
        'unit_refund',
    ];

    protected $casts = [
        'after_sale_id' => 'integer',
        'order_item_id' => 'integer',
        'quantity' => 'integer',
        'unit_refund' => Money::class,
    ];

    public function afterSale(): BelongsTo
    {
        return $this->belongsTo(AfterSale::class);
    }
}
