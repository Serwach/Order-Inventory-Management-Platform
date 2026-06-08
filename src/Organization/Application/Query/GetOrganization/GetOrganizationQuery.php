<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetOrganization;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetOrganizationQuery implements QueryInterface
{
    public function __construct(public readonly string $organizationId) {}
}
