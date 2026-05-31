<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => '手机号必填',
            'phone.regex' => '手机号格式不正确',
            'password.required' => '密码必填',
            'password.min' => '密码至少6位',
        ];
    }
}
