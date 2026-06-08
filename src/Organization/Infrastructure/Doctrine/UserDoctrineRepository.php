<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine;

use App\Organization\Domain\Entity\User;
use App\Organization\Domain\Repository\UserRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\UserId;
use App\Shared\Domain\Repository\PaginatedResult;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Pagination;
use Doctrine\ORM\EntityManagerInterface;

final class UserDoctrineRepository implements UserRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(UserId $id): ?User
    {
        return $this->em->find(User::class, $id->value());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.email = :email')
            ->setParameter('email', $email->value())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByOrganization(OrganizationId $organizationId, Pagination $pagination): PaginatedResult
    {
        $qb = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.organizationId = :orgId')
            ->setParameter('orgId', $organizationId->value())
            ->orderBy('u.createdAt', 'DESC');

        $total = (clone $qb)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $users = $qb
            ->setFirstResult($pagination->offset)
            ->setMaxResults($pagination->limit)
            ->getQuery()
            ->getResult();

        return new PaginatedResult(
            items: $users,
            totalCount: (int) $total,
            page: $pagination->page,
            limit: $pagination->limit,
        );
    }

    public function save(User $user): void
    {
        $this->em->persist($user);
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }
}
