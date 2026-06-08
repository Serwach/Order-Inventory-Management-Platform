<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final class InventoryAdjusted extends DomainEvent
{
    public function __construct(
        public readonly string $stockEntryId,
        public readonly string $organizationId,
        public readonly string $productId,
        public readonly string $warehouseId,
        public readonly int $previousOnHand,
        public readonly int $newOnHand,
        public readonly int $delta,
        public readonly string $reason,
    ) {
        parent::__construct();
    }

    public function eventName(): string { return 'inventory.adjusted'; }

    protected function payload(): array
    {
        return [
            'stock_entry_id'  => $this->stockEntryId,
            'organization_id' => $this->organizationId,
            'product_id'      => $this->productId,
            'warehouse_id'    => $this->warehouseId,
            'previous_on_hand' => $this->previousOnHand,
            'new_on_hand'     => $this->newOnHand,
            'delta'           => $this->delta,
            'reason'          => $this->reason,
        ];
    }
}
