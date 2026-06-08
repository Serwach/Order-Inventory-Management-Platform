<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final class OrganizationCreated extends DomainEvent
{
    public function __construct(
        public readonly string $organizationId,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $plan,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'organization.created';
    }

    protected function payload(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'plan'            => $this->plan,
        ];
    }
}
