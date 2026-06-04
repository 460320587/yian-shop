<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class UpdateProfileRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'nickname' => ['sometimes', 'string', 'max:50'],
            'avatar' => ['sometimes', 'string', 'url', 'max:500'],
            'link_person' => ['sometimes', 'string', 'max:50'],
            'qq' => ['sometimes', 'string', 'max:20'],
        ];
    }
}
