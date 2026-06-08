<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\User\Models\Customer;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LoginSmsRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendSmsCodeRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\SmsCodeService;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

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
        $phone = $request->input('phone');
        $rateKey = 'login:' . $phone;

        // 防暴力破解：5次失败后锁定15分钟
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return $this->error(ErrorCode::AUTH_TOO_MANY_ATTEMPTS, '登录失败次数过多，请15分钟后再试', null, 429);
        }

        $data = $request->validated();

        $customer = Customer::where('phone', $data['phone'])->first();

        if (!$customer || !Hash::check($data['password'], $customer->password)) {
            RateLimiter::hit($rateKey, 15 * 60);
            return $this->error(ErrorCode::AUTH_LOGIN_FAILED, '手机号或密码错误', null, 401);
        }

        // 成功登录清除失败计数
        RateLimiter::clear($rateKey);

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
            'link_person' => $customer->link_person,
            'qq' => $customer->qq,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $customer = auth('sanctum')->user();
        $data = $request->validated();

        $customer->update($data);

        return $this->success([
            'id' => $customer->id,
            'phone' => $customer->phone,
            'nickname' => $customer->nickname,
            'avatar' => $customer->avatar,
            'type' => $customer->type,
            'auth_status' => $customer->auth_status,
            'vip_level' => $customer->vip_level,
            'balance' => $customer->balance?->toYuan(),
            'link_person' => $customer->link_person,
            'qq' => $customer->qq,
        ], '更新成功');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $customer = Customer::where('phone', $request->input('phone'))->first();

        if (! $customer) {
            return $this->error(ErrorCode::USER_NOT_FOUND, '用户不存在', null, 404);
        }

        $token = bin2hex(random_bytes(32));

        $customer->update([
            'reset_token' => $token,
            'reset_token_expires_at' => now()->addHour(),
        ]);

        return $this->success([
            'token' => $token,
        ], '重置令牌已生成');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $customer = Customer::where('phone', $request->input('phone'))->first();

        if (! $customer) {
            return $this->error(ErrorCode::USER_NOT_FOUND, '用户不存在', null, 404);
        }

        if ($customer->reset_token !== $request->input('token')) {
            return $this->error(ErrorCode::AUTH_TOKEN_INVALID, '重置令牌无效');
        }

        if ($customer->reset_token_expires_at && $customer->reset_token_expires_at < now()) {
            return $this->error(ErrorCode::AUTH_TOKEN_EXPIRED, '重置令牌已过期');
        }

        $customer->update([
            'password' => Hash::make($request->input('password')),
            'reset_token' => null,
            'reset_token_expires_at' => null,
        ]);

        return $this->success([], '密码重置成功');
    }

    public function captcha(): JsonResponse
    {
        $key = 'captcha_' . uniqid();
        $code = (string) random_int(1000, 9999);

        cache()->put($key, $code, now()->addMinutes(5));

        return $this->success([
            'captcha_key' => $key,
            'captcha_code' => $code,
        ]);
    }

    public function sendSmsCode(SendSmsCodeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $rateKey = 'sms_rate:' . $data['phone'];

        // 短信验证码限流：3次/分钟（SmsCodeService 内部有日限流 10次/天）
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return $this->error(ErrorCode::SMS_SEND_TOO_FREQUENT, '请求过于频繁，请稍后再试', null, 429);
        }

        RateLimiter::hit($rateKey, 60);

        (new SmsCodeService())->send(
            $data['phone'],
            $data['captcha_key'],
            $data['captcha_code'],
        );

        return $this->success([], '验证码已发送');
    }

    public function loginSms(LoginSmsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $verified = (new SmsCodeService())->verify($data['phone'], $data['sms_code']);

        if (! $verified) {
            return $this->error(ErrorCode::SMS_CODE_INVALID, '短信验证码错误或已过期');
        }

        $customer = Customer::where('phone', $data['phone'])->first();

        if (! $customer) {
            $customer = Customer::create([
                'phone' => $data['phone'],
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'nickname' => '用户' . substr($data['phone'], -4),
                'register_ip' => $request->ip(),
            ]);
        } else {
            $customer->update(['last_login_at' => now()]);
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

    public function checkPhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
        ]);

        $exists = Customer::where('phone', $request->input('phone'))->exists();

        return $this->success(['available' => ! $exists]);
    }
}
