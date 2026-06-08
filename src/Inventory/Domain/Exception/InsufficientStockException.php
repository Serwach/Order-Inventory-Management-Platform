<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InsufficientStockException extends DomainException
{
    public static function forProduct(
        string $productId,
        int $requested,
        int $available,
    ): self {
        return new self(
            sprintf(
                'Insufficient stock for product "%s": requested %d, available %d.',
                $productId,
                $requested,
                $available
            )
        );
    }
}
