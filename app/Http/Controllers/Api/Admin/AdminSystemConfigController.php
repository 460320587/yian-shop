<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\System\Models\SystemConfig;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSystemConfigController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = SystemConfig::query();

        if ($group = $request->input('group')) {
            $query->where('group', $group);
        }
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword): void {
                $q->where('config_key', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $configs = $query->orderBy('group')->orderBy('config_key')->get();

        return $this->success($configs->map(fn (SystemConfig $c) => [
            'id' => $c->id,
            'config_key' => $c->config_key,
            'config_value' => $c->config_value,
            'type' => $c->type,
            'description' => $c->description,
            'group' => $c->group,
        ]));
    }

    public function show(int $id): JsonResponse
    {
        $config = SystemConfig::findOrFail($id);

        return $this->success([
            'id' => $config->id,
            'config_key' => $config->config_key,
            'config_value' => $config->config_value,
            'type' => $config->type,
            'description' => $config->description,
            'group' => $config->group,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $config = SystemConfig::findOrFail($id);

        $data = $request->validate([
            'config_value' => 'required|string',
        ]);

        $config->update($data);

        return $this->success([], '更新成功');
    }

    public function batchUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'configs' => 'required|array',
            'configs.*.id' => 'required|integer|exists:system_configs,id',
            'configs.*.config_value' => 'required|string',
        ]);

        foreach ($data['configs'] as $item) {
            SystemConfig::where('id', $item['id'])->update(['config_value' => $item['config_value']]);
        }

        return $this->success([], '批量更新成功');
    }
}
