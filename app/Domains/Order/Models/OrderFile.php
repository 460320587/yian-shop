<?php

declare(strict_types=1);

namespace App\Domains\Order\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Enterprise\Models\CustomerBrand;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFile extends BaseModel
{
    protected $table = 'order_files';

    protected $fillable = [
        'order_id',
        'file_url',
        'thumb_url',
        'page_count',
        'ink_coverage',
        'brand_id',
        'file_name',
        'file_size',
        'file_type',
        'archive_path',
        'archive_status',
        'version',
        'status',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'page_count' => 'integer',
        'ink_coverage' => 'float',
        'brand_id' => 'integer',
        'file_size' => 'integer',
        'archive_status' => 'integer',
        'version' => 'integer',
        'status' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CustomerBrand::class);
    }
}
