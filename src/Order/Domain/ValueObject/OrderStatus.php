<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

enum OrderStatus: string
{
    case PENDING   = 'pending';
    case CONFIRMED = 'confirmed';
    case PAID      = 'paid';
    case SHIPPED   = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING   => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PAID, self::CANCELLED],
            self::PAID      => [self::SHIPPED],
            self::SHIPPED   => [self::DELIVERED],
            self::DELIVERED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }

    public function isFinal(): bool
    {
        return $this === self::DELIVERED || $this === self::CANCELLED;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
