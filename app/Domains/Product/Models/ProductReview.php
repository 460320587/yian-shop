<?php

declare(strict_types=1);

namespace App\Domains\Product\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends BaseModel
{
    use SoftDeletes;

    protected $table = 'product_reviews';

    protected $fillable = [
        'customer_id',
        'product_id',
        'order_id',
        'rating',
        'content',
        'images',
        'reply',
        'reply_at',
        'is_show',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'product_id' => 'integer',
        'order_id' => 'integer',
        'rating' => 'integer',
        'images' => 'array',
        'is_show' => 'boolean',
        'reply_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
