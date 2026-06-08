<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateOrganization;

use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Entity\User;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\Repository\UserRepositoryInterface;
use App\Organization\Domain\ValueObject\TenantSlug;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateOrganizationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherFactoryInterface $hasherFactory,
    ) {}

    public function __invoke(CreateOrganizationCommand $command): Organization
    {
        $slug = TenantSlug::fromName($command->name);

        if ($this->organizations->findBySlug($slug) !== null) {
            throw ConflictException::alreadyExists('Organization', $slug->value());
        }

        $email = Email::fromString($command->ownerEmail);

        if ($this->users->findByEmail($email) !== null) {
            throw ConflictException::alreadyExists('User', $email->value());
        }

        $orgId = $this->organizations->nextIdentity();

        $organization = Organization::create(
            id: $orgId,
            name: $command->name,
            slug: $slug,
            plan: $command->plan,
        );

        $this->organizations->save($organization);

        // Create the org owner in the same transaction
        $owner = User::register(
            id: $this->users->nextIdentity(),
            organizationId: $orgId,
            organization: $organization,
            email: $email,
            hashedPassword: $this->hasherFactory
                ->getPasswordHasher(User::class)
                ->hash($command->ownerPassword),
            firstName: $command->ownerFirstName,
            lastName: $command->ownerLastName,
            role: 'ROLE_OWNER',
        );

        $this->users->save($owner);

        return $organization;
    }
}
