<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ErrorCode;
use Exception;
use Throwable;

class BusinessException extends Exception
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message ?? $errorCode->message(),
            $errorCode->value,
            $previous
        );
    }

    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->errorCode->httpStatus();
    }
}
