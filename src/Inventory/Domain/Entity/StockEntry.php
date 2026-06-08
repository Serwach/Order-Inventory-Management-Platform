<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entity;

use App\Inventory\Domain\Event\InventoryAdjusted;
use App\Inventory\Domain\Event\StockReserved;
use App\Inventory\Domain\Event\ReservationReleased;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Shared\Domain\Aggregate\AggregateRoot;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents on-hand stock for a single (product, warehouse) combination.
 *
 * Invariants:
 * - availableQuantity = onHand - reserved
 * - reserved ≥ 0
 * - onHand ≥ reserved (enforced by reserveStock)
 */
#[ORM\Entity]
#[ORM\Table(name: 'stock_entries')]
#[ORM\UniqueConstraint(name: 'uniq_stock_product_warehouse', columns: ['product_id', 'warehouse_id', 'organization_id'])]
#[ORM\Index(name: 'idx_stock_organization', columns: ['organization_id'])]
class StockEntry extends AggregateRoot
{
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 0;

    #[ORM\OneToMany(targetEntity: StockMovement::class, mappedBy: 'stockEntry', cascade: ['persist'])]
    private Collection $movements;

    #[ORM\OneToMany(targetEntity: StockReservation::class, mappedBy: 'stockEntry', cascade: ['persist', 'remove'])]
    private Collection $reservations;

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $organizationId,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $productId,

        #[ORM\Column(length: 36)]
        private readonly string $warehouseId,

        #[ORM\Column(type: 'integer')]
        private int $onHand,

        #[ORM\Column(type: 'integer')]
        private int $reserved,

        #[ORM\Column(type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $createdAt,

        #[ORM\Column(type: 'datetime_immutable')]
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->movements    = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    public static function create(
        string $id,
        string $organizationId,
        string $productId,
        string $warehouseId,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            id: $id,
            organizationId: $organizationId,
            productId: $productId,
            warehouseId: $warehouseId,
            onHand: 0,
            reserved: 0,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * Adjust on-hand stock (positive = inbound, negative = outbound correction).
     */
    public function adjust(Quantity $delta, string $reason, string $referenceId): void
    {
        $newOnHand = $this->onHand + $delta->value();

        if ($newOnHand < 0) {
            throw new \DomainException(
                sprintf('Adjustment would result in negative on-hand stock (%d).', $newOnHand)
            );
        }

        if ($newOnHand < $this->reserved) {
            throw new \DomainException(
                sprintf(
                    'Adjustment would make on-hand (%d) lower than reserved (%d).',
                    $newOnHand,
                    $this->reserved
                )
            );
        }

        $previousOnHand = $this->onHand;
        $this->onHand   = $newOnHand;
        $this->updatedAt = new \DateTimeImmutable();

        $movement = StockMovement::create(
            id: \Symfony\Component\Uid\Uuid::v7()->toRfc4122(),
            stockEntry: $this,
            organizationId: $this->organizationId,
            delta: $delta->value(),
            reason: $reason,
            referenceId: $referenceId,
        );
        $this->movements->add($movement);

        $this->raise(new InventoryAdjusted(
            stockEntryId: $this->id,
            organizationId: $this->organizationId,
            productId: $this->productId,
            warehouseId: $this->warehouseId,
            previousOnHand: $previousOnHand,
            newOnHand: $this->onHand,
            delta: $delta->value(),
            reason: $reason,
        ));
    }

    /**
     * Reserve stock for an order. Uses optimistic locking on $version.
     */
    public function reserve(Quantity $quantity, string $orderId): StockReservation
    {
        if ($this->available()->value() < $quantity->value()) {
            throw InsufficientStockException::forProduct(
                $this->productId,
                $quantity->value(),
                $this->available()->value()
            );
        }

        $this->reserved  += $quantity->value();
        $this->updatedAt  = new \DateTimeImmutable();

        $reservation = StockReservation::create(
            id: \Symfony\Component\Uid\Uuid::v7()->toRfc4122(),
            stockEntry: $this,
            organizationId: $this->organizationId,
            quantity: $quantity->value(),
            orderId: $orderId,
        );
        $this->reservations->add($reservation);

        $this->raise(new StockReserved(
            stockEntryId: $this->id,
            organizationId: $this->organizationId,
            productId: $this->productId,
            warehouseId: $this->warehouseId,
            orderId: $orderId,
            quantity: $quantity->value(),
        ));

        return $reservation;
    }

    /**
     * Release a reservation (order cancelled or reservation expired).
     */
    public function releaseReservation(StockReservation $reservation): void
    {
        if (!$this->reservations->contains($reservation)) {
            throw new \DomainException('Reservation does not belong to this stock entry.');
        }

        $this->reserved  -= $reservation->quantity();
        $this->updatedAt  = new \DateTimeImmutable();
        $reservation->release();

        $this->raise(new ReservationReleased(
            stockEntryId: $this->id,
            organizationId: $this->organizationId,
            productId: $this->productId,
            orderId: $reservation->orderId(),
            quantity: $reservation->quantity(),
        ));
    }

    /**
     * Consume reserved stock when order ships (reserved → shipped).
     */
    public function fulfil(StockReservation $reservation): void
    {
        if (!$this->reservations->contains($reservation)) {
            throw new \DomainException('Reservation does not belong to this stock entry.');
        }

        $this->onHand   -= $reservation->quantity();
        $this->reserved -= $reservation->quantity();
        $this->updatedAt = new \DateTimeImmutable();
        $reservation->fulfil();
    }

    public function available(): Quantity
    {
        return Quantity::of($this->onHand - $this->reserved);
    }

    public function onHand(): Quantity { return Quantity::of($this->onHand); }
    public function reserved(): Quantity { return Quantity::of($this->reserved); }
    public function id(): string { return $this->id; }
    public function organizationId(): string { return $this->organizationId; }
    public function productId(): string { return $this->productId; }
    public function warehouseId(): string { return $this->warehouseId; }
    public function version(): int { return $this->version; }
}
