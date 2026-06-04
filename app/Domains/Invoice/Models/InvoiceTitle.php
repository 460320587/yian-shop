<?php

declare(strict_types=1);

namespace App\Domains\Invoice\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceTitle extends BaseModel
{
    protected $table = 'invoice_titles';

    protected $fillable = [
        'customer_id', 'title_type', 'invoice_category', 'company_name',
        'tax_number', 'register_address', 'register_phone',
        'bank_name', 'bank_account', 'is_default',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'title_type' => 'integer',
        'invoice_category' => 'integer',
        'is_default' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\User\Models\Customer::class);
    }
}
