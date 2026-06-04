<?php

declare(strict_types=1);

namespace App\Domains\Portal\Models;

use App\Domains\Common\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Banner extends BaseModel
{
    protected $table = 'banners';

    protected $fillable = [
        'title', 'image', 'image_mobile', 'link_type', 'link_target',
        'position', 'sort', 'display_start', 'display_end', 'status',
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
        'display_start' => 'datetime:Y-m-d H:i:s',
        'display_end' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeOfPosition(Builder $query, string $position): Builder
    {
        return $query->where('position', $position);
    }

    public function scopeVisible(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('status', 1)
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('display_start')
                    ->orWhere('display_start', '<=', $now);
            })
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('display_end')
                    ->orWhere('display_end', '>=', $now);
            });
    }
}
