<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine;

use App\Inventory\Domain\Entity\StockReservation;
use App\Inventory\Domain\Repository\StockReservationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class StockReservationDoctrineRepository implements StockReservationRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByOrderId(string $organizationId, string $orderId): ?StockReservation
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(StockReservation::class, 'r')
            ->where('r.organizationId = :orgId AND r.orderId = :orderId')
            ->setParameter('orgId', $organizationId)
            ->setParameter('orderId', $orderId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByOrderId(string $organizationId, string $orderId): array
    {
        return $this->em->createQueryBuilder()
            ->select('r')
            ->from(StockReservation::class, 'r')
            ->where('r.organizationId = :orgId AND r.orderId = :orderId AND r.status = :status')
            ->setParameter('orgId', $organizationId)
            ->setParameter('orderId', $orderId)
            ->setParameter('status', StockReservation::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }
}
