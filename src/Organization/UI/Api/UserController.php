<?php

declare(strict_types=1);

namespace App\Organization\UI\Api;

use App\Organization\Application\Query\GetOrganization\GetOrganizationQuery;
use App\Organization\Domain\Entity\User;
use App\Shared\Application\Query\QueryBusInterface;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CurrentUserProvider $currentUser,
    ) {}

    #[Route('/me', name: 'user_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->currentUser->getUser();

        return new JsonResponse($this->serializeUser($user));
    }

    #[Route('/me/organization', name: 'user_organization', methods: ['GET'])]
    public function organization(): JsonResponse
    {
        $org = $this->queryBus->ask(
            new GetOrganizationQuery($this->currentUser->getOrganizationId())
        );

        return new JsonResponse([
            'id'         => $org->id()->value(),
            'name'       => $org->name(),
            'slug'       => $org->slug()->value(),
            'plan'       => $org->plan(),
            'active'     => $org->isActive(),
            'created_at' => $org->createdAt()->format(\DateTimeInterface::RFC3339),
        ]);
    }

    /** @return array<string, mixed> */
    private function serializeUser(User $user): array
    {
        return [
            'id'              => $user->getId()->value(),
            'organization_id' => $user->getOrganizationId(),
            'email'           => $user->getEmail()->value(),
            'first_name'      => $user->getFirstName(),
            'last_name'       => $user->getLastName(),
            'full_name'       => $user->getFullName(),
            'roles'           => $user->getRoles(),
            'active'          => $user->isActive(),
            'created_at'      => $user->createdAt()->format(\DateTimeInterface::RFC3339),
            'last_login_at'   => $user->lastLoginAt()?->format(\DateTimeInterface::RFC3339),
        ];
    }
}
