<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\GetStockLevel;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetStockLevelQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public ?string $warehouseId = null,
    ) {}
}
