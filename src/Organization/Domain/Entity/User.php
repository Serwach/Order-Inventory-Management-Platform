<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\Event\UserRegistered;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\UserId;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
#[ORM\Index(name: 'idx_user_organization', columns: ['organization_id'])]
class User extends AggregateRoot implements UserInterface, PasswordAuthenticatedUserInterface
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $organizationId,

        #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'users')]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
        private readonly Organization $organization,

        #[ORM\Column(length: 180, unique: true)]
        private string $email,

        #[ORM\Column]
        private string $passwordHash,

        #[ORM\Column(length: 100)]
        private string $firstName,

        #[ORM\Column(length: 100)]
        private string $lastName,

        #[ORM\Column(type: 'json')]
        private array $roles,

        #[ORM\Column]
        private bool $active,

        #[ORM\Column(type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $createdAt,

        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        private ?\DateTimeImmutable $lastLoginAt = null,
    ) {}

    public static function register(
        UserId $id,
        OrganizationId $organizationId,
        Organization $organization,
        Email $email,
        string $hashedPassword,
        string $firstName,
        string $lastName,
        string $role = 'ROLE_USER',
    ): self {
        $user = new self(
            id: $id->value(),
            organizationId: $organizationId->value(),
            organization: $organization,
            email: $email->value(),
            passwordHash: $hashedPassword,
            firstName: trim($firstName),
            lastName: trim($lastName),
            roles: [$role],
            active: true,
            createdAt: new \DateTimeImmutable(),
        );

        $user->raise(new UserRegistered(
            userId: $id->value(),
            organizationId: $organizationId->value(),
            email: $email->value(),
            firstName: $firstName,
            lastName: $lastName,
        ));

        return $user;
    }

    public function recordLogin(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
    }

    public function changePassword(string $hashedPassword): void
    {
        $this->passwordHash = $hashedPassword;
    }

    public function promote(string $role): void
    {
        if (!in_array($role, $this->roles, strict: true)) {
            $this->roles[] = $role;
        }
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function getId(): UserId
    {
        return UserId::fromString($this->id);
    }

    public function getOrganizationId(): string
    {
        return $this->organizationId;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getEmail(): Email
    {
        return Email::fromString($this->email);
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function lastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    // ─── UserInterface ────────────────────────────────────────────────────────

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void {}
}
