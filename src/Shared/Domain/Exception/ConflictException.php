<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class ConflictException extends DomainException
{
    public static function alreadyExists(string $resource, string $identifier): self
    {
        return new self(sprintf('%s "%s" already exists.', $resource, $identifier));
    }

    public static function optimisticLock(string $resource, string $id): self
    {
        return new self(
            sprintf(
                '%s "%s" was modified by another process. Please retry.',
                $resource,
                $id
            )
        );
    }
}
