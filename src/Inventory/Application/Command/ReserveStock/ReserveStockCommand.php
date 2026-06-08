<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ReserveStock;

use App\Shared\Application\Command\CommandInterface;

final readonly class ReserveStockCommand implements CommandInterface
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public string $warehouseId,
        public int $quantity,
        public string $orderId,
    ) {}
}
