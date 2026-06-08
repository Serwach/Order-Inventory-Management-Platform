<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Exception\DomainException;

final class InvalidOrderTransitionException extends DomainException
{
    public static function unexpectedStatus(
        OrderId $orderId,
        OrderStatus $expected,
        OrderStatus $actual,
        string $action,
    ): self {
        return new self(
            sprintf(
                'Cannot %s order "%s": expected status "%s", got "%s".',
                $action,
                $orderId->value(),
                $expected->value,
                $actual->value
            )
        );
    }

    public static function cannotCancel(OrderId $orderId, OrderStatus $current): self
    {
        return new self(
            sprintf(
                'Cannot cancel order "%s" in status "%s".',
                $orderId->value(),
                $current->value
            )
        );
    }

    public static function invalidTransition(OrderId $orderId, OrderStatus $from, OrderStatus $to): self
    {
        return new self(
            sprintf(
                'Order "%s" cannot transition from "%s" to "%s".',
                $orderId->value(),
                $from->value,
                $to->value
            )
        );
    }
}
