<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

abstract class BaseRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                ErrorCode::VALIDATION_ERROR,
                '请求参数校验失败',
                $validator->errors()->toArray()
            )
        );
    }

    public function expectsJson(): bool
    {
        return true;
    }
}
