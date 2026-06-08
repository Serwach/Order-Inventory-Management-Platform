<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Money;

final class PaymentConfirmed extends DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $organizationId,
        public readonly string $orderNumber,
        public readonly string $paymentId,
        public readonly Money $total,
    ) {
        parent::__construct();
    }

    public function eventName(): string { return 'order.payment.confirmed'; }

    protected function payload(): array
    {
        return [
            'order_id'        => $this->orderId,
            'organization_id' => $this->organizationId,
            'order_number'    => $this->orderNumber,
            'payment_id'      => $this->paymentId,
            'total_amount'    => $this->total->amount(),
            'currency'        => $this->total->currency(),
        ];
    }
}
