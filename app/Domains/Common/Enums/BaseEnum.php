<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use BackedEnum;

trait BaseEnum
{
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public function label(): string
    {
        return match ($this->value) {
            default => $this->name,
        };
    }

    public static function fromOrNull(int|string|null $value): ?static
    {
        if ($value === null) {
            return null;
        }
        return self::tryFrom($value);
    }
}
