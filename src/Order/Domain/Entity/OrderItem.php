<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
#[ORM\Index(name: 'idx_order_item_order', columns: ['order_id'])]
class OrderItem
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
        #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false)]
        private readonly Order $order,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $productId,

        #[ORM\Column(type: 'string', length: 36, nullable: true)]
        private readonly ?string $variantId,

        #[ORM\Column(length: 50)]
        private readonly string $sku,

        #[ORM\Column(length: 200)]
        private readonly string $productName,

        #[ORM\Column(type: 'integer')]
        private readonly int $quantity,

        #[ORM\Column(type: 'integer')]
        private readonly int $unitPriceAmount,

        #[ORM\Column(length: 3)]
        private readonly string $currency,
    ) {}

    public static function create(
        string $id,
        Order $order,
        string $productId,
        ?string $variantId,
        string $sku,
        string $productName,
        int $quantity,
        Money $unitPrice,
    ): self {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Order item quantity must be at least 1.');
        }

        return new self(
            id: $id,
            order: $order,
            productId: $productId,
            variantId: $variantId,
            sku: $sku,
            productName: $productName,
            quantity: $quantity,
            unitPriceAmount: $unitPrice->amount(),
            currency: $unitPrice->currency(),
        );
    }

    public function lineTotal(): Money
    {
        return Money::of($this->unitPriceAmount, $this->currency)->multiply($this->quantity);
    }

    public function unitPrice(): Money
    {
        return Money::of($this->unitPriceAmount, $this->currency);
    }

    public function id(): string { return $this->id; }
    public function productId(): string { return $this->productId; }
    public function variantId(): ?string { return $this->variantId; }
    public function sku(): string { return $this->sku; }
    public function productName(): string { return $this->productName; }
    public function quantity(): int { return $this->quantity; }
    public function currency(): string { return $this->currency; }
}
