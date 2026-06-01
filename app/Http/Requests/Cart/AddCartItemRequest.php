<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\BaseRequest;

class AddCartItemRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => '商品ID必填',
            'product_id.integer' => '商品ID必须是整数',
            'quantity.required' => '数量必填',
            'quantity.integer' => '数量必须是整数',
            'quantity.min' => '数量至少为1',
            'quantity.max' => '数量不能超过100000',
        ];
    }
}
