<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\GetStockLevel;

use App\Inventory\Domain\Repository\StockEntryRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetStockLevelHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly StockEntryRepositoryInterface $stockEntries,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(GetStockLevelQuery $query): array
    {
        $entries = $query->warehouseId !== null
            ? array_filter([
                $this->stockEntries->findByProductAndWarehouse(
                    $query->organizationId,
                    $query->productId,
                    $query->warehouseId,
                ),
            ])
            : $this->stockEntries->findByProduct($query->organizationId, $query->productId);

        $totalOnHand    = 0;
        $totalReserved  = 0;
        $totalAvailable = 0;
        $warehouseData  = [];

        foreach ($entries as $entry) {
            $totalOnHand    += $entry->onHand()->value();
            $totalReserved  += $entry->reserved()->value();
            $totalAvailable += $entry->available()->value();

            $warehouseData[] = [
                'warehouse_id' => $entry->warehouseId(),
                'on_hand'      => $entry->onHand()->value(),
                'reserved'     => $entry->reserved()->value(),
                'available'    => $entry->available()->value(),
            ];
        }

        return [
            'product_id'        => $query->productId,
            'total_on_hand'     => $totalOnHand,
            'total_reserved'    => $totalReserved,
            'total_available'   => $totalAvailable,
            'warehouses'        => $warehouseData,
        ];
    }
}
