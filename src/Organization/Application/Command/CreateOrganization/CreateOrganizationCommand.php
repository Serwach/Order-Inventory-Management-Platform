<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateOrganization;

use App\Shared\Application\Command\CommandInterface;

final readonly class CreateOrganizationCommand implements CommandInterface
{
    public function __construct(
        public string $name,
        public string $plan,
        public string $ownerEmail,
        public string $ownerPassword,
        public string $ownerFirstName,
        public string $ownerLastName,
    ) {}
}
