<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AdjustStock;

use App\Inventory\Domain\Entity\StockEntry;
use App\Inventory\Domain\Repository\StockEntryRepositoryInterface;
use App\Inventory\Domain\ValueObject\Quantity;
use App\Shared\Application\Command\CommandHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AdjustStockHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly StockEntryRepositoryInterface $stockEntries,
    ) {}

    public function __invoke(AdjustStockCommand $command): StockEntry
    {
        $entry = $this->stockEntries->findByProductAndWarehouse(
            $command->organizationId,
            $command->productId,
            $command->warehouseId,
        );

        if ($entry === null) {
            $entry = StockEntry::create(
                id: $this->stockEntries->nextIdentity(),
                organizationId: $command->organizationId,
                productId: $command->productId,
                warehouseId: $command->warehouseId,
            );
            $this->stockEntries->save($entry);
        }

        $entry->adjust(
            delta: Quantity::of(abs($command->delta)),
            reason: $command->reason,
            referenceId: $command->referenceId ?? '',
        );

        return $entry;
    }
}
