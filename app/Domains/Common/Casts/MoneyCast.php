<?php

declare(strict_types=1);

namespace App\Domains\Common\Casts;

use App\Domains\Common\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return new Money((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Money) {
            return [$key => $value->amount];
        }

        return [$key => (int) $value];
    }
}
