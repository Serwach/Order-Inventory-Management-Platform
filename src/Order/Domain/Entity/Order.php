<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\Event\OrderCancelled;
use App\Order\Domain\Event\OrderConfirmed;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Event\PaymentConfirmed;
use App\Order\Domain\Event\ShipmentCreated;
use App\Order\Domain\Exception\InvalidOrderTransitionException;
use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\OrderNumber;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Order aggregate root.
 *
 * Enforces business invariants:
 * - Status transitions are validated against the allowed FSM
 * - Items are immutable after placement
 * - Totals are computed from items (no setter injection)
 * - Optimistic locking prevents concurrent conflicting updates
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\UniqueConstraint(name: 'uniq_order_number', columns: ['number'])]
#[ORM\Index(name: 'idx_order_organization_status', columns: ['organization_id', 'status'])]
#[ORM\Index(name: 'idx_order_customer', columns: ['organization_id', 'customer_id'])]
class Order extends AggregateRoot
{
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 0;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EAGER')]
    private Collection $items;

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $organizationId,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $customerId,

        #[ORM\Embedded(class: OrderNumber::class, columnPrefix: false)]
        private readonly OrderNumber $number,

        #[ORM\Column(length: 20, enumType: OrderStatus::class)]
        private OrderStatus $status,

        #[ORM\Column(type: 'integer')]
        private int $subtotalAmount,

        #[ORM\Column(length: 3)]
        private string $currency,

        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $notes,

        #[ORM\Column(type: 'json')]
        private array $shippingAddress,

        #[ORM\Column(type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $placedAt,

        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        private ?\DateTimeImmutable $confirmedAt = null,

        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        private ?\DateTimeImmutable $paidAt = null,

        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        private ?\DateTimeImmutable $cancelledAt = null,

        #[ORM\Column(type: 'string', length: 36, nullable: true)]
        private ?string $paymentId = null,

        #[ORM\Column(length: 100, nullable: true)]
        private ?string $cancellationReason = null,
    ) {
        $this->items = new ArrayCollection();
    }

    /**
     * Factory: create and place a new order.
     *
     * @param list<array{productId:string, variantId:string|null, sku:string, name:string, quantity:int, unitPrice:Money}> $itemData
     */
    public static function place(
        OrderId $id,
        string $organizationId,
        string $customerId,
        OrderNumber $number,
        array $itemData,
        array $shippingAddress,
        string $currency,
        ?string $notes = null,
    ): self {
        if ($itemData === []) {
            throw new \DomainException('An order must contain at least one item.');
        }

        $order = new self(
            id: $id->value(),
            organizationId: $organizationId,
            customerId: $customerId,
            number: $number,
            status: OrderStatus::PENDING,
            subtotalAmount: 0,
            currency: $currency,
            notes: $notes,
            shippingAddress: $shippingAddress,
            placedAt: new \DateTimeImmutable(),
        );

        foreach ($itemData as $data) {
            $item = OrderItem::create(
                id: \Symfony\Component\Uid\Uuid::v7()->toRfc4122(),
                order: $order,
                productId: $data['productId'],
                variantId: $data['variantId'] ?? null,
                sku: $data['sku'],
                productName: $data['name'],
                quantity: $data['quantity'],
                unitPrice: $data['unitPrice'],
            );
            $order->items->add($item);
            $order->subtotalAmount += $item->lineTotal()->amount();
        }

        $order->raise(new OrderCreated(
            orderId: $id->value(),
            organizationId: $organizationId,
            customerId: $customerId,
            orderNumber: $number->value(),
            total: $order->total(),
        ));

        return $order;
    }

    public function confirm(): void
    {
        $this->ensureTransition(OrderStatus::CONFIRMED);
        $this->status      = OrderStatus::CONFIRMED;
        $this->confirmedAt = new \DateTimeImmutable();

        $this->raise(new OrderConfirmed(
            orderId: $this->id,
            organizationId: $this->organizationId,
            orderNumber: $this->number->value(),
        ));
    }

    public function markAsPaid(string $paymentId): void
    {
        $this->ensureTransition(OrderStatus::PAID);
        $this->status    = OrderStatus::PAID;
        $this->paymentId = $paymentId;
        $this->paidAt    = new \DateTimeImmutable();

        $this->raise(new PaymentConfirmed(
            orderId: $this->id,
            organizationId: $this->organizationId,
            orderNumber: $this->number->value(),
            paymentId: $paymentId,
            total: $this->total(),
        ));
    }

    public function ship(string $trackingNumber, string $carrier): void
    {
        $this->ensureTransition(OrderStatus::SHIPPED);
        $this->status = OrderStatus::SHIPPED;

        $this->raise(new ShipmentCreated(
            orderId: $this->id,
            organizationId: $this->organizationId,
            orderNumber: $this->number->value(),
            trackingNumber: $trackingNumber,
            carrier: $carrier,
        ));
    }

    public function deliver(): void
    {
        $this->ensureTransition(OrderStatus::DELIVERED);
        $this->status = OrderStatus::DELIVERED;
    }

    public function cancel(string $reason): void
    {
        if ($this->status->isFinal()) {
            throw InvalidOrderTransitionException::cannotCancel($this->id(), $this->status);
        }

        $this->status               = OrderStatus::CANCELLED;
        $this->cancellationReason   = $reason;
        $this->cancelledAt          = new \DateTimeImmutable();

        $this->raise(new \App\Order\Domain\Event\OrderCancelled(
            orderId: $this->id,
            organizationId: $this->organizationId,
            orderNumber: $this->number->value(),
            reason: $reason,
        ));
    }

    public function total(): Money
    {
        return Money::of($this->subtotalAmount, $this->currency);
    }

    public function id(): OrderId { return OrderId::fromString($this->id); }
    public function organizationId(): string { return $this->organizationId; }
    public function customerId(): string { return $this->customerId; }
    public function number(): OrderNumber { return $this->number; }
    public function status(): OrderStatus { return $this->status; }
    public function shippingAddress(): array { return $this->shippingAddress; }
    public function notes(): ?string { return $this->notes; }
    public function placedAt(): \DateTimeImmutable { return $this->placedAt; }
    public function confirmedAt(): ?\DateTimeImmutable { return $this->confirmedAt; }
    public function paidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function cancelledAt(): ?\DateTimeImmutable { return $this->cancelledAt; }
    public function paymentId(): ?string { return $this->paymentId; }
    public function cancellationReason(): ?string { return $this->cancellationReason; }
    public function version(): int { return $this->version; }

    /** @return Collection<int, OrderItem> */
    public function items(): Collection { return $this->items; }

    private function ensureTransition(OrderStatus $next): void
    {
        if (!$this->status->canTransitionTo($next)) {
            throw InvalidOrderTransitionException::invalidTransition(
                $this->id(),
                $this->status,
                $next
            );
        }
    }
}
