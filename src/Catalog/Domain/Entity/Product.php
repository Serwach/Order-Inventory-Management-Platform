<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\Event\ProductCreated;
use App\Catalog\Domain\Event\ProductUpdated;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Domain\ValueObject\Sku;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\UniqueConstraint(name: 'uniq_product_org_sku', columns: ['organization_id', 'sku'])]
#[ORM\Index(name: 'idx_product_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_product_active', columns: ['organization_id', 'active'])]
class Product extends AggregateRoot
{
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 0;

    #[ORM\OneToMany(targetEntity: ProductVariant::class, mappedBy: 'product', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $variants;

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $id,

        #[ORM\Column(type: 'string', length: 36)]
        private readonly string $organizationId,

        #[ORM\Column(length: 50)]
        private string $sku,

        #[ORM\Column(length: 200)]
        private string $name,

        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $description,

        #[ORM\Column(type: 'integer')]
        private int $basePrice,

        #[ORM\Column(length: 3)]
        private string $currency,

        #[ORM\Column(length: 50, nullable: true)]
        private ?string $category,

        #[ORM\Column(type: 'json')]
        private array $attributes,

        #[ORM\Column]
        private bool $active,

        #[ORM\Column(type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $createdAt,

        #[ORM\Column(type: 'datetime_immutable')]
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->variants = new ArrayCollection();
    }

    public static function create(
        ProductId $id,
        string $organizationId,
        Sku $sku,
        string $name,
        Money $basePrice,
        ?string $description = null,
        ?string $category = null,
        array $attributes = [],
    ): self {
        $now = new \DateTimeImmutable();

        $product = new self(
            id: $id->value(),
            organizationId: $organizationId,
            sku: $sku->value(),
            name: trim($name),
            description: $description,
            basePrice: $basePrice->amount(),
            currency: $basePrice->currency(),
            category: $category,
            attributes: $attributes,
            active: true,
            createdAt: $now,
            updatedAt: $now,
        );

        $product->raise(new ProductCreated(
            productId: $id->value(),
            organizationId: $organizationId,
            sku: $sku->value(),
            name: $name,
            basePrice: $basePrice,
        ));

        return $product;
    }

    public function update(
        string $name,
        Money $basePrice,
        ?string $description,
        ?string $category,
        array $attributes,
    ): void {
        $this->name        = trim($name);
        $this->basePrice   = $basePrice->amount();
        $this->currency    = $basePrice->currency();
        $this->description = $description;
        $this->category    = $category;
        $this->attributes  = $attributes;
        $this->updatedAt   = new \DateTimeImmutable();

        $this->raise(new ProductUpdated(
            productId: $this->id,
            organizationId: $this->organizationId,
            sku: $this->sku,
            name: $this->name,
            basePrice: $basePrice,
        ));
    }

    public function addVariant(ProductVariant $variant): void
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
        }
    }

    public function deactivate(): void
    {
        $this->active    = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->active    = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): ProductId { return ProductId::fromString($this->id); }
    public function organizationId(): string { return $this->organizationId; }
    public function sku(): Sku { return Sku::fromString($this->sku); }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }
    public function basePrice(): Money { return Money::of($this->basePrice, $this->currency); }
    public function category(): ?string { return $this->category; }
    public function attributes(): array { return $this->attributes; }
    public function isActive(): bool { return $this->active; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function version(): int { return $this->version; }

    /** @return Collection<int, ProductVariant> */
    public function variants(): Collection { return $this->variants; }
}
