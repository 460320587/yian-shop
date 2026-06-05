<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPreference extends BaseModel
{
    protected $table = 'customer_preferences';

    protected $fillable = [
        'customer_id',
        'product_layout_type',
        'category_grid_type',
        'user_center_menu_fold',
        'pay_now',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'product_layout_type' => 'integer',
        'category_grid_type' => 'integer',
        'user_center_menu_fold' => 'integer',
        'pay_now' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
