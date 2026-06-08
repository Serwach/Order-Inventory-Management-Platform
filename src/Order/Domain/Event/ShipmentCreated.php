<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final class ShipmentCreated extends DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $organizationId,
        public readonly string $orderNumber,
        public readonly string $trackingNumber,
        public readonly string $carrier,
    ) {
        parent::__construct();
    }

    public function eventName(): string { return 'order.shipment.created'; }

    protected function payload(): array
    {
        return [
            'order_id'        => $this->orderId,
            'organization_id' => $this->organizationId,
            'order_number'    => $this->orderNumber,
            'tracking_number' => $this->trackingNumber,
            'carrier'         => $this->carrier,
        ];
    }
}
