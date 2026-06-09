<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Organization\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

final class CurrentUserProvider
{
    public function __construct(private readonly Security $security) {}

    public function getUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('No authenticated user found or user is not of expected type.');
        }

        return $user;
    }

    public function getUserId(): string
    {
        return $this->getUser()->getId()->value();
    }

    public function getOrganizationId(): string
    {
        return $this->getUser()->getOrganizationId();
    }

    public function isGranted(string $attribute): bool
    {
        return $this->security->isGranted($attribute);
    }
}
