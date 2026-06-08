<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RegisterUser;

use App\Shared\Application\Command\CommandInterface;

final readonly class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public string $organizationId,
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
        public string $role = 'ROLE_USER',
    ) {}
}
