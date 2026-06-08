<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final class StockReserved extends DomainEvent
{
    public function __construct(
        public readonly string $stockEntryId,
        public readonly string $organizationId,
        public readonly string $productId,
        public readonly string $warehouseId,
        public readonly string $orderId,
        public readonly int $quantity,
    ) {
        parent::__construct();
    }

    public function eventName(): string { return 'inventory.stock.reserved'; }

    protected function payload(): array
    {
        return [
            'stock_entry_id'  => $this->stockEntryId,
            'organization_id' => $this->organizationId,
            'product_id'      => $this->productId,
            'warehouse_id'    => $this->warehouseId,
            'order_id'        => $this->orderId,
            'quantity'        => $this->quantity,
        ];
    }
}
