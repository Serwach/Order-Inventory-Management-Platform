<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

final readonly class Sku implements \Stringable
{
    private string $value;

    private function __construct(string $value)
    {
        $value = strtoupper(trim($value));

        if (preg_match('/^[A-Z0-9][A-Z0-9\-_]{1,49}$/', $value) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid SKU (2-50 chars, alphanumeric, hyphens or underscores only).', $value)
            );
        }

        $this->value = $value;
    }

    public static function fromString(string $sku): self
    {
        return new self($sku);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
