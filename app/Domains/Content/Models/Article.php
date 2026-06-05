<?php

declare(strict_types=1);

namespace App\Domains\Content\Models;

use App\Domains\Common\Models\BaseModel;

class Article extends BaseModel
{
    protected $table = 'articles';

    protected $fillable = [
        'title', 'slug', 'type', 'content', 'summary',
        'cover', 'author', 'view_count', 'sort', 'status', 'published_at',
    ];

    protected $casts = [
        'type' => 'integer',
        'view_count' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'published_at' => 'datetime:Y-m-d H:i:s',
    ];
}
