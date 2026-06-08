<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Security;

use App\Organization\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<\App\Organization\Domain\Entity\User>
 */
final class JwtUserProvider implements UserProviderInterface
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findByEmail(Email::fromString($identifier));

        if ($user === null) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        if (!$user->isActive()) {
            throw new UserNotFoundException(sprintf('User "%s" is deactivated.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof \App\Organization\Domain\Entity\User) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === \App\Organization\Domain\Entity\User::class
            || is_subclass_of($class, \App\Organization\Domain\Entity\User::class);
    }
}
