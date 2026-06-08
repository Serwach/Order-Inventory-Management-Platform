<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Automatically scopes all queries to the current organization (tenant).
 * Activated per-request by TenantFilterListener after JWT authentication.
 */
final class TenantFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->hasField('organizationId')) {
            return '';
        }

        try {
            $organizationId = $this->getParameter('organizationId');
        } catch (\InvalidArgumentException) {
            return '';
        }

        return sprintf(
            '%s.organization_id = %s',
            $targetTableAlias,
            $organizationId
        );
    }
}
