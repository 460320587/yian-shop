<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class RegisterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/', 'unique:customers,phone'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$/'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'link_person' => ['nullable', 'string', 'max:50'],
            'qq' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => '手机号必填',
            'phone.regex' => '手机号格式不正确',
            'phone.unique' => '该手机号已被注册',
            'password.required' => '密码必填',
            'password.confirmed' => '两次密码输入不一致',
            'password.regex' => '密码长度至少8位，需包含字母和数字',
        ];
    }
}
