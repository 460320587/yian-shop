<?php

declare(strict_types=1);

namespace App\Domains\Product\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends BaseModel
{
    protected $fillable = [
        'parent_id',
        'name',
        'icon',
        'sort',
        'status',
        'level',
        'path',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'level' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
