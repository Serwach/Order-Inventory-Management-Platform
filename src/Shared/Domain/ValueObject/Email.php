<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final readonly class Email implements \Stringable
{
    private string $value;

    private function __construct(string $value)
    {
        $normalised = strtolower(trim($value));

        if (!filter_var($normalised, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid email address.', $value)
            );
        }

        $this->value = $normalised;
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
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
