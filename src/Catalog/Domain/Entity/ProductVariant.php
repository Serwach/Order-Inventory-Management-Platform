<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\ValueObject\Sku;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_variants')]
#[ORM\UniqueConstraint(name: 'uniq_variant_org_sku', columns: ['organization_id', 'sku'])]
class ProductVariant
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'variants')]
        #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
        private readonly Product $product,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $organizationId,

        #[ORM\Column(length: 50)]
        private readonly string $sku,

        #[ORM\Column(length: 200)]
        private string $name,

        #[ORM\Column(type: 'integer', nullable: true)]
        private ?int $priceOverride,

        #[ORM\Column(length: 3)]
        private string $currency,

        #[ORM\Column(type: 'json')]
        private array $attributes,

        #[ORM\Column]
        private bool $active,
    ) {}

    public static function create(
        string $id,
        Product $product,
        string $organizationId,
        Sku $sku,
        string $name,
        ?Money $priceOverride = null,
        array $attributes = [],
    ): self {
        return new self(
            id: $id,
            product: $product,
            organizationId: $organizationId,
            sku: $sku->value(),
            name: $name,
            priceOverride: $priceOverride?->amount(),
            currency: $priceOverride?->currency() ?? $product->basePrice()->currency(),
            attributes: $attributes,
            active: true,
        );
    }

    public function effectivePrice(): Money
    {
        return $this->priceOverride !== null
            ? Money::of($this->priceOverride, $this->currency)
            : $this->product->basePrice();
    }

    public function id(): string { return $this->id; }
    public function sku(): Sku { return Sku::fromString($this->sku); }
    public function name(): string { return $this->name; }
    public function product(): Product { return $this->product; }
    public function attributes(): array { return $this->attributes; }
    public function isActive(): bool { return $this->active; }
}
