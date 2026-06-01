<?php

declare(strict_types=1);

namespace App\Domains\Cart\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends BaseModel
{
    protected $fillable = [
        'customer_id',
        'total_count',
        'selected_subtotal',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'total_count' => 'integer',
        'selected_subtotal' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
