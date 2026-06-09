<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid as SymfonyUuid;

abstract class Uuid implements \Stringable
{
    private readonly string $value;

    final private function __construct(string $value)
    {
        if (!SymfonyUuid::isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid UUID (expected RFC 4122).', $value)
            );
        }

        $this->value = strtolower($value);
    }

    final public static function generate(): static
    {
        return new static(SymfonyUuid::v7()->toRfc4122());
    }

    final public static function fromString(string $uuid): static
    {
        return new static($uuid);
    }

    final public function value(): string
    {
        return $this->value;
    }

    final public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    final public function __toString(): string
    {
        return $this->value;
    }
}
