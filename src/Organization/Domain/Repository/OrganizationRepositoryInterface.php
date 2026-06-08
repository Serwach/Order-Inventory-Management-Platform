<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\TenantSlug;

interface OrganizationRepositoryInterface
{
    public function findById(OrganizationId $id): ?Organization;

    public function findBySlug(TenantSlug $slug): ?Organization;

    public function save(Organization $organization): void;

    public function nextIdentity(): OrganizationId;
}
