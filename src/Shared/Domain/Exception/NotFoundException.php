<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class NotFoundException extends DomainException
{
    public static function forId(string $resource, string $id): self
    {
        return new self(sprintf('%s with id "%s" not found.', $resource, $id));
    }

    public static function forCriteria(string $resource, string $criteria): self
    {
        return new self(sprintf('%s matching "%s" not found.', $resource, $criteria));
    }
}
