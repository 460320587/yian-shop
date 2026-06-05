<?php

declare(strict_types=1);

namespace App\Domains\Common\StateMachines\Exceptions;

use RuntimeException;

class InvalidTransitionException extends RuntimeException
{
}
