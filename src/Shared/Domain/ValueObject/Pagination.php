<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final readonly class Pagination
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $offset,
    ) {}

    public static function fromRequest(int $page, int $limit, int $maxLimit = 100): self
    {
        $page  = max(1, $page);
        $limit = min(max(1, $limit), $maxLimit);

        return new self(
            page: $page,
            limit: $limit,
            offset: ($page - 1) * $limit,
        );
    }
}
