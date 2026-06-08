<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final class UserInvited extends DomainEvent
{
    public function __construct(
        public readonly string $invitationId,
        public readonly string $organizationId,
        public readonly string $email,
        public readonly string $token,
        public readonly string $role,
        public readonly \DateTimeImmutable $expiresAt,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'organization.user.invited';
    }

    protected function payload(): array
    {
        return [
            'invitation_id'   => $this->invitationId,
            'organization_id' => $this->organizationId,
            'email'           => $this->email,
            'role'            => $this->role,
            'expires_at'      => $this->expiresAt->format(\DateTimeInterface::RFC3339),
        ];
    }
}
