<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine;

use App\Inventory\Domain\Entity\StockEntry;
use App\Inventory\Domain\Repository\StockEntryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class StockEntryDoctrineRepository implements StockEntryRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(string $id): ?StockEntry
    {
        return $this->em->find(StockEntry::class, $id);
    }

    public function findByProductAndWarehouse(
        string $organizationId,
        string $productId,
        string $warehouseId,
    ): ?StockEntry {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(StockEntry::class, 's')
            ->where('s.organizationId = :orgId AND s.productId = :productId AND s.warehouseId = :warehouseId')
            ->setParameter('orgId', $organizationId)
            ->setParameter('productId', $productId)
            ->setParameter('warehouseId', $warehouseId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByProduct(string $organizationId, string $productId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(StockEntry::class, 's')
            ->where('s.organizationId = :orgId AND s.productId = :productId')
            ->setParameter('orgId', $organizationId)
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getResult();
    }

    public function save(StockEntry $entry): void
    {
        $this->em->persist($entry);
    }

    public function nextIdentity(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
