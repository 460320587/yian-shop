<?php

declare(strict_types=1);

namespace App\Domains\Product\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFavorite extends BaseModel
{
    protected $table = 'customer_favorites';

    protected $fillable = [
        'customer_id',
        'product_id',
        'remark',
        'status',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'product_id' => 'integer',
        'status' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\User\Models\Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
