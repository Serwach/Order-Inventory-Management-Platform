<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final readonly class Money implements \Stringable
{
    private function __construct(
        private int $amount,
        private string $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException(
                sprintf('Money amount cannot be negative, got %d.', $amount)
            );
        }

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('Invalid ISO-4217 currency code: "%s".', $currency)
            );
        }
    }

    public static function of(int $amountInCents, string $currency): self
    {
        return new self($amountInCents, strtoupper($currency));
    }

    public static function zero(string $currency = 'USD'): self
    {
        return new self(0, strtoupper($currency));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        if ($other->amount > $this->amount) {
            throw new \InvalidArgumentException('Cannot subtract: result would be negative.');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new \InvalidArgumentException('Multiplier cannot be negative.');
        }

        return new self($this->amount * $multiplier, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount >= $other->amount;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function formatted(): string
    {
        return sprintf('%s %.2f', $this->currency, $this->amount / 100);
    }

    public function __toString(): string
    {
        return $this->formatted();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Currency mismatch: cannot operate on %s and %s.',
                    $this->currency,
                    $other->currency
                )
            );
        }
    }
}
