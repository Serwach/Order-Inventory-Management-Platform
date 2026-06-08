<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Money;

final class ProductCreated extends DomainEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $organizationId,
        public readonly string $sku,
        public readonly string $name,
        public readonly Money $basePrice,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'catalog.product.created';
    }

    protected function payload(): array
    {
        return [
            'product_id'      => $this->productId,
            'organization_id' => $this->organizationId,
            'sku'             => $this->sku,
            'name'            => $this->name,
            'base_price'      => $this->basePrice->amount(),
            'currency'        => $this->basePrice->currency(),
        ];
    }
}
