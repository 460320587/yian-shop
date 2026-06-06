<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class SendSmsCodeRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'captcha_key' => ['required', 'string'],
            'captcha_code' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => '手机号必填',
            'phone.regex' => '手机号格式不正确',
            'captcha_key.required' => '图形验证码标识必填',
            'captcha_code.required' => '图形验证码必填',
        ];
    }
}
