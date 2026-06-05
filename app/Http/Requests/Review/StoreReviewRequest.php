<?php

declare(strict_types=1);

namespace App\Http\Requests\Review;

use App\Http\Requests\BaseRequest;

class StoreReviewRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string', 'min:5', 'max:500'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => '商品 ID 必填',
            'rating.required' => '请评分',
            'rating.between' => '评分必须在 1-5 之间',
            'content.required' => '评价内容必填',
            'content.min' => '评价内容至少 5 个字符',
            'content.max' => '评价内容最多 500 个字符',
        ];
    }
}
