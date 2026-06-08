<?php

declare(strict_types=1);

namespace App\Order\Application\EventHandler;

use App\Inventory\Application\Command\ReserveStock\ReserveStockCommand;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderId;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Event\EventHandlerInterface;
use App\Inventory\Domain\Exception\InsufficientStockException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Reserves inventory for each item in a newly created order.
 * Runs async. If stock reservation fails, the order should be cancelled.
 *
 * Note: In a fully saga-based system this would be a saga step.
 * Here we handle it as a compensating workflow (order → reserve → if fail → cancel).
 */
#[AsMessageHandler(bus: 'event.bus')]
final class ReserveInventoryOnOrderCreated implements EventHandlerInterface
{
    private const DEFAULT_WAREHOUSE = 'default';

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly OrderRepositoryInterface $orders,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(OrderCreated $event): void
    {
        $order = $this->orders->findByIdAndOrganization(
            OrderId::fromString($event->orderId),
            $event->organizationId
        );

        if ($order === null) {
            $this->logger->error('Order not found for inventory reservation', [
                'order_id' => $event->orderId,
            ]);

            return;
        }

        $reservationFailed = false;

        foreach ($order->items() as $item) {
            try {
                $this->commandBus->dispatch(new ReserveStockCommand(
                    organizationId: $event->organizationId,
                    productId: $item->productId(),
                    warehouseId: self::DEFAULT_WAREHOUSE,
                    quantity: $item->quantity(),
                    orderId: $event->orderId,
                ));

                $this->logger->info('Stock reserved', [
                    'order_id'   => $event->orderId,
                    'product_id' => $item->productId(),
                    'quantity'   => $item->quantity(),
                ]);
            } catch (InsufficientStockException $e) {
                $this->logger->warning('Insufficient stock, will cancel order', [
                    'order_id'   => $event->orderId,
                    'product_id' => $item->productId(),
                    'message'    => $e->getMessage(),
                ]);

                $reservationFailed = true;
                break;
            }
        }

        if ($reservationFailed) {
            $this->commandBus->dispatch(
                new \App\Order\Application\Command\CancelOrder\CancelOrderCommand(
                    orderId: $event->orderId,
                    organizationId: $event->organizationId,
                    reason: 'Insufficient stock for one or more items.',
                )
            );
        } else {
            $order->confirm();
        }
    }
}
