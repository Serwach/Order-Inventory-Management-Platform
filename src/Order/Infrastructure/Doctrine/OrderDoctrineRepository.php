<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Doctrine;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Repository\PaginatedResult;
use App\Shared\Domain\ValueObject\Pagination;
use Doctrine\ORM\EntityManagerInterface;

final class OrderDoctrineRepository implements OrderRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(OrderId $id): ?Order
    {
        return $this->em->find(Order::class, $id->value());
    }

    public function findByIdAndOrganization(OrderId $id, string $organizationId): ?Order
    {
        return $this->em->createQueryBuilder()
            ->select('o', 'i')
            ->from(Order::class, 'o')
            ->leftJoin('o.items', 'i')
            ->where('o.id = :id AND o.organizationId = :orgId')
            ->setParameter('id', $id->value())
            ->setParameter('orgId', $organizationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByOrganization(
        string $organizationId,
        array $filters,
        Pagination $pagination,
    ): PaginatedResult {
        $qb = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.organizationId = :orgId')
            ->setParameter('orgId', $organizationId);

        if (isset($filters['customer_id'])) {
            $qb->andWhere('o.customerId = :customerId')
                ->setParameter('customerId', $filters['customer_id']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['from'])) {
            $qb->andWhere('o.placedAt >= :from')
                ->setParameter('from', new \DateTimeImmutable($filters['from']));
        }

        if (isset($filters['to'])) {
            $qb->andWhere('o.placedAt <= :to')
                ->setParameter('to', new \DateTimeImmutable($filters['to']));
        }

        $allowedSortFields = ['placedAt', 'status', 'subtotalAmount'];
        $sortBy  = in_array($filters['sort_by'] ?? 'placedAt', $allowedSortFields, strict: true)
            ? $filters['sort_by']
            : 'placedAt';
        $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $total = (clone $qb)
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $orders = $qb
            ->orderBy('o.' . $sortBy, $sortDir)
            ->setFirstResult($pagination->offset)
            ->setMaxResults($pagination->limit)
            ->getQuery()
            ->getResult();

        return new PaginatedResult(
            items: $orders,
            totalCount: (int) $total,
            page: $pagination->page,
            limit: $pagination->limit,
        );
    }

    public function save(Order $order): void
    {
        $this->em->persist($order);
    }

    public function nextIdentity(): OrderId
    {
        return OrderId::generate();
    }
}
