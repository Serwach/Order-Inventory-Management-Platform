<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stock_reservations')]
#[ORM\Index(name: 'idx_reservation_order', columns: ['order_id'])]
#[ORM\Index(name: 'idx_reservation_organization', columns: ['organization_id'])]
class StockReservation
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_RELEASED  = 'released';

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\ManyToOne(targetEntity: StockEntry::class, inversedBy: 'reservations')]
        #[ORM\JoinColumn(name: 'stock_entry_id', referencedColumnName: 'id', nullable: false)]
        private readonly StockEntry $stockEntry,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $organizationId,

        #[ORM\Column(type: 'integer')]
        private readonly int $quantity,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $orderId,

        #[ORM\Column(length: 20)]
        private string $status,

        #[ORM\Column(type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $createdAt,

        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        private ?\DateTimeImmutable $resolvedAt = null,
    ) {}

    public static function create(
        string $id,
        StockEntry $stockEntry,
        string $organizationId,
        int $quantity,
        string $orderId,
    ): self {
        return new self(
            id: $id,
            stockEntry: $stockEntry,
            organizationId: $organizationId,
            quantity: $quantity,
            orderId: $orderId,
            status: self::STATUS_ACTIVE,
            createdAt: new \DateTimeImmutable(),
        );
    }

    public function release(): void
    {
        $this->status     = self::STATUS_RELEASED;
        $this->resolvedAt = new \DateTimeImmutable();
    }

    public function fulfil(): void
    {
        $this->status     = self::STATUS_FULFILLED;
        $this->resolvedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }
    public function id(): string { return $this->id; }
    public function quantity(): int { return $this->quantity; }
    public function orderId(): string { return $this->orderId; }
    public function status(): string { return $this->status; }
}
