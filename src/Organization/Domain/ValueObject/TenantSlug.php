<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

final readonly class TenantSlug implements \Stringable
{
    private string $value;

    private function __construct(string $value)
    {
        $value = strtolower(trim($value));

        if (preg_match('/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$/', $value) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid tenant slug (3-63 lowercase alphanumeric or hyphen, no leading/trailing hyphens).', $value)
            );
        }

        $this->value = $value;
    }

    public static function fromString(string $slug): self
    {
        return new self($slug);
    }

    public static function fromName(string $name): self
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
        $slug = trim((string) $slug, '-');

        return new self(substr($slug, 0, 63));
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
