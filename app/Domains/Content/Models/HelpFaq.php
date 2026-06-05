<?php

declare(strict_types=1);

namespace App\Domains\Content\Models;

use App\Domains\Common\Models\BaseModel;

class HelpFaq extends BaseModel
{
    protected $table = 'help_faqs';

    protected $fillable = [
        'category_id',
        'question',
        'answer',
        'keywords',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];
}
