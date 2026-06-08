<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Security;

use App\Organization\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class TenantFilterListener
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $filters = $this->em->getFilters();

        if (!$filters->isEnabled('tenant')) {
            $filter = $filters->enable('tenant');
            $filter->setParameter('organizationId', sprintf("'%s'", $user->getOrganizationId()));
        }
    }
}
