<?php

declare(strict_types=1);

namespace App\Http\Requests\Address;

use App\Http\Requests\BaseRequest;

class StoreAddressRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'min:2', 'max:20'],
            'contact_phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'province_name' => ['required', 'string', 'min:2', 'max:30'],
            'city_name' => ['required', 'string', 'min:2', 'max:30'],
            'county_name' => ['required', 'string', 'min:2', 'max:30'],
            'detail_address' => ['required', 'string', 'min:5', 'max:200'],
            'zip_code' => ['nullable', 'regex:/^\d{6}$/'],
            'is_default' => ['nullable', 'boolean'],
            'tag' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_name.required' => '联系人姓名必填',
            'contact_phone.required' => '联系电话必填',
            'contact_phone.regex' => '手机号格式不正确',
            'province_name.required' => '省必填',
            'city_name.required' => '市必填',
            'county_name.required' => '区/县必填',
            'detail_address.required' => '详细地址必填',
            'zip_code.regex' => '邮编格式不正确',
        ];
    }
}
