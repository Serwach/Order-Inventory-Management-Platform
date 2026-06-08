<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetOrganization;

use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOrganizationHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
    ) {}

    public function __invoke(GetOrganizationQuery $query): Organization
    {
        $organization = $this->organizations->findById(
            OrganizationId::fromString($query->organizationId)
        );

        if ($organization === null) {
            throw NotFoundException::forId('Organization', $query->organizationId);
        }

        return $organization;
    }
}
