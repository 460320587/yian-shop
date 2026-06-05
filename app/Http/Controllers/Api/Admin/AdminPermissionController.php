<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Admin\Models\AdminPermission;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPermissionController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = AdminPermission::query();

        if ($request->filled('group')) {
            $query->where('group', $request->input('group'));
        }

        $permissions = $query->orderBy('group')->orderBy('id')->get();

        return $this->success($permissions->map(fn (AdminPermission $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'group' => $p->group,
            'type' => $p->type,
        ]));
    }
}
