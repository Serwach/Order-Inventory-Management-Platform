<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RegisterUser;

use App\Organization\Domain\Entity\User;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\Repository\UserRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler(bus: 'command.bus')]
final class RegisterUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly OrganizationRepositoryInterface $organizations,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function __invoke(RegisterUserCommand $command): User
    {
        $orgId = OrganizationId::fromString($command->organizationId);

        $organization = $this->organizations->findById($orgId);

        if ($organization === null) {
            throw NotFoundException::forId('Organization', $command->organizationId);
        }

        $email = Email::fromString($command->email);

        if ($this->users->findByEmail($email) !== null) {
            throw ConflictException::alreadyExists('User', $email->value());
        }

        $userId = $this->users->nextIdentity();

        $placeholder = new class extends User {
            public function __construct() { /* skip parent init */ }
        };

        $user = User::register(
            id: $userId,
            organizationId: $orgId,
            organization: $organization,
            email: $email,
            hashedPassword: $this->hasher->hashPassword($placeholder, $command->password),
            firstName: $command->firstName,
            lastName: $command->lastName,
            role: $command->role,
        );

        $this->users->save($user);

        return $user;
    }
}
