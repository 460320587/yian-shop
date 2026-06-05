<?php

declare(strict_types=1);

namespace App\Domains\Product\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParamTemplate extends BaseModel
{
    protected $table = 'param_templates';

    protected $fillable = [
        'category_id',
        'param_type',
        'param_name',
        'options',
        'rules',
        'version',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'version' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'options' => 'array',
        'rules' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }
}
