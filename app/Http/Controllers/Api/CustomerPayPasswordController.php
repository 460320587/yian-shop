<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Payment\Models\CustomerPayPassword;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerPayPasswordController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        $customerId = auth('sanctum')->id();

        $validated = $request->validate([
            'pay_password' => ['required', 'string', 'min:6', 'max:20', 'confirmed'],
        ]);

        if (CustomerPayPassword::where('customer_id', $customerId)->exists()) {
            return $this->error(ErrorCode::VALIDATION_ERROR, '支付密码已设置');
        }

        $payPassword = new CustomerPayPassword();
        $payPassword->customer_id = $customerId;
        $payPassword->setPayPassword($validated['pay_password']);
        $payPassword->save();

        return $this->success(['has_pay_password' => true], '支付密码设置成功', 201);
    }

    public function update(Request $request): JsonResponse
    {
        $customerId = auth('sanctum')->id();

        $validated = $request->validate([
            'old_pay_password' => ['required', 'string'],
            'pay_password' => ['required', 'string', 'min:6', 'max:20', 'confirmed'],
        ]);

        $payPassword = CustomerPayPassword::where('customer_id', $customerId)->first();

        if (! $payPassword) {
            return $this->error(ErrorCode::NOT_FOUND, '未设置支付密码', null, 404);
        }

        if ($payPassword->isLocked()) {
            return $this->error(ErrorCode::PAY_PASSWORD_LOCKED, '支付密码已锁定，请稍后再试');
        }

        if (! $payPassword->verify($validated['old_pay_password'])) {
            throw ValidationException::withMessages([
                'old_pay_password' => ['原支付密码不正确'],
            ]);
        }

        $payPassword->setPayPassword($validated['pay_password']);
        $payPassword->save();

        return $this->success(['has_pay_password' => true], '支付密码修改成功');
    }

    public function verify(Request $request): JsonResponse
    {
        $customerId = auth('sanctum')->id();

        $validated = $request->validate([
            'pay_password' => ['required', 'string'],
        ]);

        $payPassword = CustomerPayPassword::where('customer_id', $customerId)->first();

        if (! $payPassword) {
            return $this->success(['valid' => false, 'has_pay_password' => false]);
        }

        if ($payPassword->isLocked()) {
            return $this->success([
                'valid' => false,
                'has_pay_password' => true,
                'locked' => true,
                'locked_until' => $payPassword->locked_until,
            ]);
        }

        $valid = $payPassword->verify($validated['pay_password']);

        return $this->success([
            'valid' => $valid,
            'has_pay_password' => true,
            'fail_count' => $payPassword->fail_count,
        ]);
    }

    public function status(): JsonResponse
    {
        $customerId = auth('sanctum')->id();
        $payPassword = CustomerPayPassword::where('customer_id', $customerId)->first();

        return $this->success([
            'has_pay_password' => $payPassword !== null,
            'locked' => $payPassword?->isLocked() ?? false,
            'locked_until' => $payPassword?->locked_until,
        ]);
    }
}
