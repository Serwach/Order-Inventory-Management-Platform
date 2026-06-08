<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ReserveStock;

use App\Inventory\Domain\Entity\StockReservation;
use App\Inventory\Domain\Repository\StockEntryRepositoryInterface;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ReserveStockHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly StockEntryRepositoryInterface $stockEntries,
    ) {}

    public function __invoke(ReserveStockCommand $command): StockReservation
    {
        $entry = $this->stockEntries->findByProductAndWarehouse(
            $command->organizationId,
            $command->productId,
            $command->warehouseId,
        );

        if ($entry === null) {
            throw NotFoundException::forCriteria(
                'StockEntry',
                "product={$command->productId} warehouse={$command->warehouseId}"
            );
        }

        // reserve() enforces the invariant: available ≥ quantity
        // Optimistic locking via @Version catches concurrent reservation conflicts
        return $entry->reserve(
            quantity: Quantity::of($command->quantity),
            orderId: $command->orderId,
        );
    }
}
