<?php

declare(strict_types=1);

namespace App\Order\Application\Command\PlaceOrder;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Order\Domain\Service\OrderNumberGeneratorInterface;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\ValueObject\Money;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class PlaceOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderNumberGeneratorInterface $numberGenerator,
    ) {}

    public function __invoke(PlaceOrderCommand $command): Order
    {
        $orderId      = $this->orders->nextIdentity();
        $orderNumber  = $this->numberGenerator->next($command->organizationId);

        $itemData = array_map(
            static fn (array $item): array => [
                'productId' => $item['productId'],
                'variantId' => $item['variantId'] ?? null,
                'sku'       => $item['sku'],
                'name'      => $item['name'],
                'quantity'  => $item['quantity'],
                'unitPrice' => Money::of($item['unitPriceAmount'], $item['currency'] ?? $command->currency),
            ],
            $command->items
        );

        $order = Order::place(
            id: $orderId,
            organizationId: $command->organizationId,
            customerId: $command->customerId,
            number: $orderNumber,
            itemData: $itemData,
            shippingAddress: $command->shippingAddress,
            currency: $command->currency,
            notes: $command->notes,
        );

        $this->orders->save($order);

        return $order;
    }
}
