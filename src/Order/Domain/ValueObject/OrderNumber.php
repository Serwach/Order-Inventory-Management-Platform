<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class OrderNumber implements \Stringable
{
    #[ORM\Column(name: 'number', length: 30, unique: true)]
    private readonly string $value;

    private function __construct(string $value)
    {
        if (preg_match('/^ORD-\d{4}-\d{6}$/', $value) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid order number (expected format: ORD-YYYY-NNNNNN).', $value)
            );
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function generate(int $year, int $sequence): self
    {
        return new self(sprintf('ORD-%d-%06d', $year, $sequence));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
