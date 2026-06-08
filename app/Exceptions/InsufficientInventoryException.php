<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InsufficientInventoryException extends Exception
{
    public function __construct(string $message = '库存不足')
    {
        parent::__construct($message);
    }
}
