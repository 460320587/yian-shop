<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Vip\Models\VipLevel;
use App\Http\Controllers\BaseController;
use App\Services\Cache\CacheService;
use Illuminate\Http\JsonResponse;

class VipController extends BaseController
{
    public function __construct(private readonly CacheService $cacheService) {}

    public function info(): JsonResponse
    {
        $customer = auth('sanctum')->user();
        $currentLevel = VipLevel::where('level', $customer->vip_level)->first();
        $nextLevel = VipLevel::where('min_points', '>', $customer->grow_value)
            ->orderBy('min_points', 'asc')
            ->first();

        $progress = [
            'current_grow_value' => $customer->grow_value,
            'current_level' => $customer->vip_level,
            'current_level_name' => $currentLevel?->name ?? '普通会员',
            'current_discount' => $currentLevel?->discount ?? 1.00,
        ];

        if ($nextLevel) {
            $min = $currentLevel?->min_points ?? 0;
            $max = $nextLevel->min_points;
            $range = max(1, $max - $min);
            $progress['next_level'] = $nextLevel->level;
            $progress['next_level_name'] = $nextLevel->name;
            $progress['next_level_points'] = $nextLevel->min_points;
            $progress['progress_percent'] = min(100, round((($customer->grow_value - $min) / $range) * 100, 2));
        } else {
            $progress['next_level'] = null;
            $progress['next_level_name'] = null;
            $progress['next_level_points'] = null;
            $progress['progress_percent'] = 100;
        }

        return $this->success($progress);
    }

    public function levels(): JsonResponse
    {
        $levels = $this->cacheService->remember('vip_levels', function () {
            return VipLevel::orderBy('level', 'asc')->get()->map(function (VipLevel $level) {
                return [
                    'level' => $level->level,
                    'name' => $level->name,
                    'min_points' => $level->min_points,
                    'discount' => $level->discount,
                    'icon' => $level->icon,
                    'privileges' => $level->privileges,
                ];
            })->all();
        }, 3600);

        return $this->success($levels);
    }

    public function discounts(): JsonResponse
    {
        $customer = auth('sanctum')->user();
        $level = VipLevel::where('level', $customer->vip_level)->first();

        return $this->success([
            'vip_level' => $customer->vip_level,
            'vip_name' => $level?->name ?? '普通会员',
            'print_discount' => $level?->discount ?? 1.00,
            'sample_discount' => $level?->privileges['sample_discount'] ?? null,
            'deadline_extension' => $level?->privileges['deadline_extension'] ?? null,
        ]);
    }
}
