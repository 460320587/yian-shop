<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Admin\Models\Admin;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends BaseController
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('username', $data['username'])->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return $this->error(ErrorCode::AUTH_LOGIN_FAILED, '用户名或密码错误');
        }

        if ($admin->status !== 1) {
            return $this->error(ErrorCode::USER_DISABLED, '账号已被禁用');
        }

        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $admin->createToken('admin-api', ['*'])->plainTextToken;

        return $this->success([
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'real_name' => $admin->real_name,
                'role' => $admin->role,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('admin')?->currentAccessToken()->delete();

        return $this->success([], '退出成功');
    }

    public function profile(Request $request): JsonResponse
    {
        $admin = $request->user('admin');

        return $this->success([
            'id' => $admin->id,
            'username' => $admin->username,
            'real_name' => $admin->real_name,
            'phone' => $admin->phone,
            'email' => $admin->email,
            'role' => $admin->role,
            'last_login_at' => $admin->last_login_at?->toDateTimeString(),
        ]);
    }
}
