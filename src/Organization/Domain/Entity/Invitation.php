<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\Event\UserInvited;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invitations')]
#[ORM\Index(name: 'idx_invitation_token', columns: ['token'])]
#[ORM\Index(name: 'idx_invitation_email', columns: ['email'])]
class Invitation extends AggregateRoot
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $organizationId,

        #[ORM\Column(length: 180)]
        private readonly string $email,

        #[ORM\Column(length: 64, unique: true)]
        private readonly string $token,

        #[ORM\Column(length: 50)]
        private readonly string $role,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $invitedByUserId,

        #[ORM\Column(type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $expiresAt,

        #[ORM\Column(type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $createdAt,

        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        private ?\DateTimeImmutable $acceptedAt = null,
    ) {}

    public static function create(
        string $id,
        OrganizationId $organizationId,
        Email $email,
        string $invitedByUserId,
        string $role = 'ROLE_USER',
        int $expiryHours = 72,
    ): self {
        $token = bin2hex(random_bytes(32));

        $invitation = new self(
            id: $id,
            organizationId: $organizationId->value(),
            email: $email->value(),
            token: $token,
            role: $role,
            invitedByUserId: $invitedByUserId,
            expiresAt: new \DateTimeImmutable(sprintf('+%d hours', $expiryHours)),
            createdAt: new \DateTimeImmutable(),
        );

        $invitation->raise(new UserInvited(
            invitationId: $id,
            organizationId: $organizationId->value(),
            email: $email->value(),
            token: $token,
            role: $role,
            expiresAt: $invitation->expiresAt,
        ));

        return $invitation;
    }

    public function accept(): void
    {
        if ($this->isAccepted()) {
            throw new \LogicException('Invitation has already been accepted.');
        }

        if ($this->isExpired()) {
            throw new \LogicException('Invitation has expired.');
        }

        $this->acceptedAt = new \DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isAccepted(): bool
    {
        return $this->acceptedAt !== null;
    }

    public function isPending(): bool
    {
        return !$this->isAccepted() && !$this->isExpired();
    }

    public function id(): string { return $this->id; }
    public function organizationId(): string { return $this->organizationId; }
    public function email(): Email { return Email::fromString($this->email); }
    public function token(): string { return $this->token; }
    public function role(): string { return $this->role; }
}
