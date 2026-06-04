<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class ForgotPasswordRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],
        ];
    }
}
