<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\User\Models\Customer;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $customer = Customer::create([
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'nickname' => $data['nickname'] ?? null,
            'link_person' => $data['link_person'] ?? null,
            'qq' => $data['qq'] ?? null,
            'register_ip' => $request->ip(),
        ]);

        $token = $customer->createToken('api')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $customer->id,
                'phone' => $customer->phone,
                'nickname' => $customer->nickname,
            ],
        ], '注册成功', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $customer = Customer::where('phone', $data['phone'])->first();

        if (!$customer || !Hash::check($data['password'], $customer->password)) {
            return $this->error(ErrorCode::AUTH_LOGIN_FAILED, '手机号或密码错误', null, 401);
        }

        $token = $customer->createToken('api')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $customer->id,
                'phone' => $customer->phone,
                'nickname' => $customer->nickname,
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        auth('sanctum')->user()?->currentAccessToken()?->delete();

        return $this->success([], '退出成功');
    }

    public function refresh(): JsonResponse
    {
        $customer = auth('sanctum')->user();

        $customer->currentAccessToken()->delete();
        $token = $customer->createToken('api')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $customer->id,
                'phone' => $customer->phone,
                'nickname' => $customer->nickname,
            ],
        ]);
    }

    public function profile(): JsonResponse
    {
        $customer = auth('sanctum')->user();

        return $this->success([
            'id' => $customer->id,
            'phone' => $customer->phone,
            'nickname' => $customer->nickname,
            'avatar' => $customer->avatar,
            'type' => $customer->type,
            'auth_status' => $customer->auth_status,
            'vip_level' => $customer->vip_level,
            'balance' => $customer->balance?->toYuan(),
        ]);
    }
}
