<?php

declare(strict_types=1);

namespace App\Order\Domain\Repository;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Repository\PaginatedResult;
use App\Shared\Domain\ValueObject\Pagination;

interface OrderRepositoryInterface
{
    public function findById(OrderId $id): ?Order;

    public function findByIdAndOrganization(OrderId $id, string $organizationId): ?Order;

    /**
     * @param array<string, mixed> $filters
     * @return PaginatedResult<Order>
     */
    public function findByOrganization(
        string $organizationId,
        array $filters,
        Pagination $pagination,
    ): PaginatedResult;

    public function save(Order $order): void;

    public function nextIdentity(): OrderId;
}
