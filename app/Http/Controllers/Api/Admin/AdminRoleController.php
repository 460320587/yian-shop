<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Admin\Models\AdminRole;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRoleController extends BaseController
{
    public function index(): JsonResponse
    {
        $roles = AdminRole::orderBy('id')->get();

        return $this->success($roles->map(fn (AdminRole $r) => [
            'id' => $r->id,
            'name' => $r->name,
            'code' => $r->code,
            'description' => $r->description,
            'status' => $r->status,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:50', 'unique:admin_roles'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);

        $role = AdminRole::create($data + ['status' => 1]);

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'code' => $role->code,
            'description' => $role->description,
            'status' => $role->status,
        ], '创建成功', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = AdminRole::find($id);
        if (! $role) {
            return $this->error(ErrorCode::NOT_FOUND, '角色不存在');
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50', 'unique:admin_roles,code,' . $role->id],
            'description' => ['nullable', 'string', 'max:200'],
        ]);

        $role->update(array_filter($data, fn ($v) => $v !== null));

        return $this->success([], '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $role = AdminRole::find($id);
        if (! $role) {
            return $this->error(ErrorCode::NOT_FOUND, '角色不存在');
        }

        $role->delete();

        return $this->success([], '删除成功');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $role = AdminRole::find($id);
        if (! $role) {
            return $this->error(ErrorCode::NOT_FOUND, '角色不存在');
        }

        $role->update(['status' => $role->status === 1 ? 0 : 1]);

        return $this->success(['status' => $role->fresh()->status]);
    }

    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $role = AdminRole::find($id);
        if (! $role) {
            return $this->error(ErrorCode::NOT_FOUND, '角色不存在');
        }

        $data = $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', 'exists:admin_permissions,id'],
        ]);

        $role->permissions()->sync($data['permission_ids']);

        return $this->success([], '权限分配成功');
    }

    public function permissions(int $id): JsonResponse
    {
        $role = AdminRole::with('permissions')->find($id);
        if (! $role) {
            return $this->error(ErrorCode::NOT_FOUND, '角色不存在');
        }

        return $this->success($role->permissions->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'group' => $p->group,
            'type' => $p->type,
            'data_scope' => $p->pivot->data_scope ?? 1,
        ]));
    }
}
