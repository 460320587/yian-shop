<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;

class CalculatePriceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'paper_id' => ['required', 'integer', 'min:1'],
            'color_id' => ['required', 'integer', 'min:1'],
            'process_ids' => ['sometimes', 'array'],
            'process_ids.*' => ['integer', 'min:1'],
        ];
    }
}
