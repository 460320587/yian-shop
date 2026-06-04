<?php

declare(strict_types=1);

namespace App\Domains\Product\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends BaseModel
{
    protected $fillable = [
        'category_id',
        'name',
        'code',
        'price_min',
        'price_max',
        'status',
        'sort',
        'cover_image',
        'thumbnail',
        'sales_count',
        'is_hot',
        'is_new',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'price_min' => Money::class,
        'price_max' => Money::class,
        'status' => 'integer',
        'sort' => 'integer',
        'sales_count' => 'integer',
        'is_hot' => 'integer',
        'is_new' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(\App\Domains\Order\Models\OrderItem::class);
    }
}
