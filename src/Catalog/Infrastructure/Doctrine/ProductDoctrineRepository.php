<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Domain\ValueObject\Sku;
use App\Shared\Domain\Repository\PaginatedResult;
use App\Shared\Domain\ValueObject\Pagination;
use Doctrine\ORM\EntityManagerInterface;

final class ProductDoctrineRepository implements ProductRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(ProductId $id): ?Product
    {
        return $this->em->find(Product::class, $id->value());
    }

    public function findByOrganizationAndSku(string $organizationId, Sku $sku): ?Product
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.organizationId = :orgId AND p.sku = :sku')
            ->setParameter('orgId', $organizationId)
            ->setParameter('sku', $sku->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function search(string $organizationId, array $filters, Pagination $pagination): PaginatedResult
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.organizationId = :orgId')
            ->setParameter('orgId', $organizationId);

        if (isset($filters['category'])) {
            $qb->andWhere('p.category = :category')->setParameter('category', $filters['category']);
        }

        if (isset($filters['active'])) {
            $qb->andWhere('p.active = :active')->setParameter('active', $filters['active']);
        }

        if (isset($filters['query'])) {
            $qb->andWhere('p.name LIKE :q OR p.sku LIKE :q')
                ->setParameter('q', '%' . $filters['query'] . '%');
        }

        $total = (clone $qb)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->orderBy('p.' . ($filters['sort_by'] ?? 'name'), strtoupper($filters['sort_dir'] ?? 'ASC'))
            ->setFirstResult($pagination->offset)
            ->setMaxResults($pagination->limit)
            ->getQuery()
            ->getResult();

        return new PaginatedResult(
            items: $items,
            totalCount: (int) $total,
            page: $pagination->page,
            limit: $pagination->limit,
        );
    }

    public function save(Product $product): void
    {
        $this->em->persist($product);
    }

    public function nextIdentity(): ProductId
    {
        return ProductId::generate();
    }
}
