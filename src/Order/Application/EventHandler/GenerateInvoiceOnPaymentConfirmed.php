<?php

declare(strict_types=1);

namespace App\Order\Application\EventHandler;

use App\Order\Domain\Event\InvoiceGenerated;
use App\Order\Domain\Event\PaymentConfirmed;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderId;
use App\Shared\Application\Event\EventHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Generates an invoice document when payment is confirmed.
 * In production this would call an invoice service/PDF generator.
 */
#[AsMessageHandler(bus: 'event.bus')]
final class GenerateInvoiceOnPaymentConfirmed implements EventHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly MessageBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(PaymentConfirmed $event): void
    {
        $order = $this->orders->findByIdAndOrganization(
            OrderId::fromString($event->orderId),
            $event->organizationId
        );

        if ($order === null) {
            $this->logger->error('Order not found for invoice generation', [
                'order_id' => $event->orderId,
            ]);

            return;
        }

        $invoiceId     = Uuid::v7()->toRfc4122();
        $invoiceNumber = sprintf(
            'INV-%d-%06d',
            (int) (new \DateTimeImmutable())->format('Y'),
            random_int(1, 999999)
        );

        $this->logger->info('Invoice generated', [
            'order_id'       => $event->orderId,
            'invoice_id'     => $invoiceId,
            'invoice_number' => $invoiceNumber,
        ]);

        // In production: persist Invoice entity, generate PDF, attach to order
        $this->eventBus->dispatch(new InvoiceGenerated(
            invoiceId: $invoiceId,
            orderId: $event->orderId,
            organizationId: $event->organizationId,
            invoiceNumber: $invoiceNumber,
            total: $event->total,
        ));
    }
}
