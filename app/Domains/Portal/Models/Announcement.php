<?php

declare(strict_types=1);

namespace App\Domains\Portal\Models;

use App\Domains\Common\Models\BaseModel;
use App\Services\Cache\CacheService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Announcement extends BaseModel
{
    protected static function booted(): void
    {
        static::saved(function () {
            app(CacheService::class)->forget('portal_home');
        });
        static::deleted(function () {
            app(CacheService::class)->forget('portal_home');
        });
    }


    protected $table = 'announcements';

    protected $fillable = [
        'title', 'content', 'type', 'is_popup', 'status', 'display_start', 'display_end',
    ];

    protected $casts = [
        'is_popup' => 'integer',
        'status' => 'integer',
        'display_start' => 'datetime:Y-m-d H:i:s',
        'display_end' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
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
