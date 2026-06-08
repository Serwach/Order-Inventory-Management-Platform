<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stock_movements')]
#[ORM\Index(name: 'idx_movement_stock_entry', columns: ['stock_entry_id'])]
#[ORM\Index(name: 'idx_movement_organization', columns: ['organization_id'])]
class StockMovement
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\ManyToOne(targetEntity: StockEntry::class, inversedBy: 'movements')]
        #[ORM\JoinColumn(name: 'stock_entry_id', referencedColumnName: 'id', nullable: false)]
        private readonly StockEntry $stockEntry,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $organizationId,

        #[ORM\Column(type: 'integer')]
        private readonly int $delta,

        #[ORM\Column(length: 100)]
        private readonly string $reason,

        #[ORM\Column(length: 36, nullable: true)]
        private readonly ?string $referenceId,

        #[ORM\Column(type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        string $id,
        StockEntry $stockEntry,
        string $organizationId,
        int $delta,
        string $reason,
        ?string $referenceId = null,
    ): self {
        return new self(
            id: $id,
            stockEntry: $stockEntry,
            organizationId: $organizationId,
            delta: $delta,
            reason: $reason,
            referenceId: $referenceId,
            createdAt: new \DateTimeImmutable(),
        );
    }

    public function id(): string { return $this->id; }
    public function delta(): int { return $this->delta; }
    public function reason(): string { return $this->reason; }
    public function referenceId(): ?string { return $this->referenceId; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
}
