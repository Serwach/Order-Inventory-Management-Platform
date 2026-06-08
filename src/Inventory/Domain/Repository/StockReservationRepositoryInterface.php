<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Entity\StockReservation;

interface StockReservationRepositoryInterface
{
    public function findByOrderId(string $organizationId, string $orderId): ?StockReservation;

    /**
     * @return list<StockReservation>
     */
    public function findActiveByOrderId(string $organizationId, string $orderId): array;
}
