<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Money;

final class InvoiceGenerated extends DomainEvent
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $orderId,
        public readonly string $organizationId,
        public readonly string $invoiceNumber,
        public readonly Money $total,
    ) {
        parent::__construct();
    }

    public function eventName(): string { return 'order.invoice.generated'; }

    protected function payload(): array
    {
        return [
            'invoice_id'      => $this->invoiceId,
            'order_id'        => $this->orderId,
            'organization_id' => $this->organizationId,
            'invoice_number'  => $this->invoiceNumber,
            'total_amount'    => $this->total->amount(),
            'currency'        => $this->total->currency(),
        ];
    }
}
