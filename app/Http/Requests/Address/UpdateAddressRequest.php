<?php

declare(strict_types=1);

namespace App\Http\Requests\Address;

use App\Http\Requests\BaseRequest;

class UpdateAddressRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'contact_name' => ['sometimes', 'string', 'min:2', 'max:20'],
            'contact_phone' => ['sometimes', 'regex:/^1[3-9]\d{9}$/'],
            'province_name' => ['sometimes', 'string', 'min:2', 'max:30'],
            'city_name' => ['sometimes', 'string', 'min:2', 'max:30'],
            'county_name' => ['sometimes', 'string', 'min:2', 'max:30'],
            'detail_address' => ['sometimes', 'string', 'min:5', 'max:200'],
            'zip_code' => ['nullable', 'regex:/^\d{6}$/'],
            'tag' => ['nullable', 'string', 'max:20'],
        ];
    }
}
