<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class LoginSmsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'sms_code' => ['required', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => '手机号必填',
            'phone.regex' => '手机号格式不正确',
            'sms_code.required' => '短信验证码必填',
            'sms_code.regex' => '短信验证码格式不正确',
        ];
    }
}
