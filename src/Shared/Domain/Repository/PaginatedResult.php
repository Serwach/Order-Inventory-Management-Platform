<?php

declare(strict_types=1);

namespace App\Shared\Domain\Repository;

/**
 * @template T
 */
final readonly class PaginatedResult
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $totalCount,
        public int $page,
        public int $limit,
    ) {}

    public function totalPages(): int
    {
        return $this->limit > 0 ? (int) ceil($this->totalCount / $this->limit) : 0;
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }
}
