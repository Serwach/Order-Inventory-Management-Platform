<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\User;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\UserId;
use App\Shared\Domain\Repository\PaginatedResult;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Pagination;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    /**
     * @return PaginatedResult<User>
     */
    public function findByOrganization(OrganizationId $organizationId, Pagination $pagination): PaginatedResult;

    public function save(User $user): void;

    public function nextIdentity(): UserId;
}
