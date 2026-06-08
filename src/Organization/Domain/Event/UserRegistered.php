<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final class UserRegistered extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $organizationId,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'organization.user.registered';
    }

    protected function payload(): array
    {
        return [
            'user_id'         => $this->userId,
            'organization_id' => $this->organizationId,
            'email'           => $this->email,
            'first_name'      => $this->firstName,
            'last_name'       => $this->lastName,
        ];
    }
}
