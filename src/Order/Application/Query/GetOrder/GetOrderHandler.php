<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrder;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderId;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOrderHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function __invoke(GetOrderQuery $query): Order
    {
        $order = $this->orders->findByIdAndOrganization(
            OrderId::fromString($query->orderId),
            $query->organizationId
        );

        if ($order === null) {
            throw NotFoundException::forId('Order', $query->orderId);
        }

        return $order;
    }
}
