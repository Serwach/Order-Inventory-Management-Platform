<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AdjustStock;

use App\Shared\Application\Command\CommandInterface;

final readonly class AdjustStockCommand implements CommandInterface
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public string $warehouseId,
        public int $delta,
        public string $reason,
        public ?string $referenceId = null,
    ) {}
}
