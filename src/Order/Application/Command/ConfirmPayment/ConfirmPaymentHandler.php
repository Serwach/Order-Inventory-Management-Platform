<?php

declare(strict_types=1);

namespace App\Order\Application\Command\ConfirmPayment;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderId;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ConfirmPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function __invoke(ConfirmPaymentCommand $command): Order
    {
        $order = $this->orders->findByIdAndOrganization(
            OrderId::fromString($command->orderId),
            $command->organizationId
        );

        if ($order === null) {
            throw NotFoundException::forId('Order', $command->orderId);
        }

        // Triggers CONFIRMED → PAID transition; raises PaymentConfirmed domain event
        $order->markAsPaid($command->paymentId);

        return $order;
    }
}
