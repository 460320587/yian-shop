<?php

declare(strict_types=1);

namespace App\Infrastructure\Actions;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class BaseAction
{
    /**
     * 执行业务操作的主入口
     */
    abstract public function handle(): mixed;

    /**
     * 在数据库事务中执行回调
     *
     * @param Closure $callback
     * @param int $attempts 重试次数
     * @return mixed
     */
    protected function transaction(Closure $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }

    /**
     * 验证输入数据
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $attributes
     * @return array 验证通过的数据
     * @throws ValidationException
     */
    protected function validate(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
