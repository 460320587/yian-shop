<?php

declare(strict_types=1);

namespace App\Domains\Common\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        public int $amount, // 单位：分
        public string $currency = 'CNY'
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('金额不能为负数');
        }
    }

    public static function fromYuan(float $yuan): self
    {
        return new self((int) round($yuan * 100));
    }

    public function toYuan(): float
    {
        return $this->amount / 100;
    }

    public function formatted(): string
    {
        return '¥' . number_format($this->toYuan(), 2);
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('币种不一致');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('币种不一致');
        }
        $newAmount = $this->amount - $other->amount;
        if ($newAmount < 0) {
            throw new InvalidArgumentException('金额不足');
        }
        return new self($newAmount, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    public function percentage(float $percent): self
    {
        return $this->multiply($percent / 100);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function greaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }
}
