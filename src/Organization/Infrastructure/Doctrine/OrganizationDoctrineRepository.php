<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine;

use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\TenantSlug;
use Doctrine\ORM\EntityManagerInterface;

final class OrganizationDoctrineRepository implements OrganizationRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(OrganizationId $id): ?Organization
    {
        return $this->em->find(Organization::class, $id->value());
    }

    public function findBySlug(TenantSlug $slug): ?Organization
    {
        return $this->em->createQueryBuilder()
            ->select('o')
            ->from(Organization::class, 'o')
            ->where('o.slug = :slug')
            ->setParameter('slug', $slug->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Organization $organization): void
    {
        $this->em->persist($organization);
    }

    public function nextIdentity(): OrganizationId
    {
        return OrganizationId::generate();
    }
}
