<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\CreateProduct;

use App\Shared\Application\Command\CommandInterface;

final readonly class CreateProductCommand implements CommandInterface
{
    public function __construct(
        public string $organizationId,
        public string $sku,
        public string $name,
        public int $basePriceAmount,
        public string $currency,
        public ?string $description = null,
        public ?string $category = null,
        public array $attributes = [],
    ) {}
}
