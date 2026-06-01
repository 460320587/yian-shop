<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\BaseRequest;

class UpdateCartItemRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'selected' => ['sometimes', 'boolean'],
        ];
    }
}
