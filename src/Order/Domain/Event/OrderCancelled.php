<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final class OrderCancelled extends DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $organizationId,
        public readonly string $orderNumber,
        public readonly string $reason,
    ) {
        parent::__construct();
    }

    public function eventName(): string { return 'order.cancelled'; }

    protected function payload(): array
    {
        return [
            'order_id'        => $this->orderId,
            'organization_id' => $this->organizationId,
            'order_number'    => $this->orderNumber,
            'reason'          => $this->reason,
        ];
    }
}
