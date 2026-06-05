<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Admin\Models\Admin;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAccountController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Admin::with('roleModel');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('real_name', 'like', "%{$keyword}%");
            });
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:admins'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'real_name' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'role_id' => ['nullable', 'integer', 'exists:admin_roles,id'],
        ]);

        $admin = Admin::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'real_name' => $data['real_name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'role' => 'operator',
            'status' => 1,
        ]);

        return $this->success([
            'id' => $admin->id,
            'username' => $admin->username,
            'real_name' => $admin->real_name,
        ], '创建成功', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $admin = Admin::find($id);
        if (! $admin) {
            return $this->error(ErrorCode::NOT_FOUND, '管理员不存在');
        }

        $data = $request->validate([
            'real_name' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'role_id' => ['nullable', 'integer', 'exists:admin_roles,id'],
        ]);

        $updateData = array_filter($data, fn ($v) => $v !== null);
        $admin->update($updateData);

        return $this->success([], '更新成功');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $admin = Admin::find($id);
        if (! $admin) {
            return $this->error(ErrorCode::NOT_FOUND, '管理员不存在');
        }

        $admin->update(['status' => $admin->status === 1 ? 0 : 1]);

        return $this->success(['status' => $admin->fresh()->status]);
    }

    public function destroy(int $id): JsonResponse
    {
        $admin = Admin::find($id);
        if (! $admin) {
            return $this->error(ErrorCode::NOT_FOUND, '管理员不存在');
        }

        if ($admin->id === auth('admin')->id()) {
            throw ValidationException::withMessages([
                'admin' => ['不能删除当前登录账号'],
            ]);
        }

        $admin->delete();

        return $this->success([], '删除成功');
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $admin = Admin::find($id);
        if (! $admin) {
            return $this->error(ErrorCode::NOT_FOUND, '管理员不存在');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $admin->update(['password' => Hash::make($data['password'])]);

        return $this->success([], '密码重置成功');
    }
}
