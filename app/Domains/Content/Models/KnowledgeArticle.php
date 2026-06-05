<?php

declare(strict_types=1);

namespace App\Domains\Content\Models;

use App\Domains\Common\Models\BaseModel;

class KnowledgeArticle extends BaseModel
{
    protected $table = 'knowledge_articles';

    protected $fillable = [
        'category_id',
        'title',
        'content',
        'summary',
        'author',
        'tags',
        'cover_image',
        'view_count',
        'like_count',
        'publish_status',
        'published_at',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'publish_status' => 'integer',
        'published_at' => 'datetime',
        'tags' => 'array',
    ];
}
