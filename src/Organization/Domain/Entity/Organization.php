<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\Event\OrganizationCreated;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\TenantSlug;
use App\Shared\Domain\Aggregate\AggregateRoot;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'organizations')]
#[ORM\UniqueConstraint(name: 'uniq_organization_slug', columns: ['slug'])]
class Organization extends AggregateRoot
{
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'organization')]
    private Collection $users;

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\Column(length: 150)]
        private string $name,

        #[ORM\Column(length: 63, unique: true)]
        private string $slug,

        #[ORM\Column(length: 50)]
        private string $plan,

        #[ORM\Column]
        private bool $active,

        #[ORM\Column(type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $createdAt,

        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        private ?\DateTimeImmutable $suspendedAt = null,
    ) {
        $this->users = new ArrayCollection();
    }

    public static function create(
        OrganizationId $id,
        string $name,
        TenantSlug $slug,
        string $plan = 'starter',
    ): self {
        $organization = new self(
            id: $id->value(),
            name: trim($name),
            slug: $slug->value(),
            plan: $plan,
            active: true,
            createdAt: new \DateTimeImmutable(),
        );

        $organization->raise(new OrganizationCreated(
            organizationId: $id->value(),
            name: $name,
            slug: $slug->value(),
            plan: $plan,
        ));

        return $organization;
    }

    public function rename(string $newName): void
    {
        $this->name = trim($newName);
    }

    public function suspend(): void
    {
        if (!$this->active) {
            return;
        }

        $this->active      = false;
        $this->suspendedAt = new \DateTimeImmutable();
    }

    public function reactivate(): void
    {
        $this->active      = true;
        $this->suspendedAt = null;
    }

    public function upgradePlan(string $plan): void
    {
        $this->plan = $plan;
    }

    public function id(): OrganizationId
    {
        return OrganizationId::fromString($this->id);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): TenantSlug
    {
        return TenantSlug::fromString($this->slug);
    }

    public function plan(): string
    {
        return $this->plan;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, User> */
    public function users(): Collection
    {
        return $this->users;
    }
}
