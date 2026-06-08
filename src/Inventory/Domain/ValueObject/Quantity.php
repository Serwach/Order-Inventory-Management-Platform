<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

final readonly class Quantity
{
    private function __construct(private int $value) {}

    public static function of(int $value): self
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(
                sprintf('Quantity cannot be negative, got %d.', $value)
            );
        }

        return new self($value);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function subtract(self $other): self
    {
        if ($other->value > $this->value) {
            throw new \InvalidArgumentException(
                sprintf('Cannot subtract %d from %d: result would be negative.', $other->value, $this->value)
            );
        }

        return new self($this->value - $other->value);
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function greaterThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->value >= $other->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function value(): int
    {
        return $this->value;
    }
}
