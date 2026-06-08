<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repository;

use App\Inventory\Domain\Entity\StockEntry;

interface StockEntryRepositoryInterface
{
    public function findById(string $id): ?StockEntry;

    public function findByProductAndWarehouse(
        string $organizationId,
        string $productId,
        string $warehouseId,
    ): ?StockEntry;

    /**
     * @return list<StockEntry>
     */
    public function findByProduct(string $organizationId, string $productId): array;

    public function save(StockEntry $entry): void;

    public function nextIdentity(): string;
}
