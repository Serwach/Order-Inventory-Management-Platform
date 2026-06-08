<?php

declare(strict_types=1);

namespace App\Inventory\UI\Api;

use App\Inventory\Application\Command\AdjustStock\AdjustStockCommand;
use App\Inventory\Application\Query\GetStockLevel\GetStockLevelQuery;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Query\QueryBusInterface;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class StockController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly CurrentUserProvider $currentUser,
    ) {}

    #[Route('/stock/{productId}', name: 'inventory_stock_level', methods: ['GET'])]
    public function level(string $productId, Request $request): JsonResponse
    {
        $level = $this->queryBus->ask(new GetStockLevelQuery(
            organizationId: $this->currentUser->getOrganizationId(),
            productId: $productId,
            warehouseId: $request->query->get('warehouse_id'),
        ));

        return new JsonResponse($level);
    }

    #[Route('/stock/{productId}/adjust', name: 'inventory_stock_adjust', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function adjust(string $productId, Request $request): JsonResponse
    {
        $data = $request->toArray();

        $this->commandBus->dispatch(new AdjustStockCommand(
            organizationId: $this->currentUser->getOrganizationId(),
            productId: $productId,
            warehouseId: $data['warehouse_id'] ?? 'default',
            delta: (int) ($data['delta'] ?? 0),
            reason: $data['reason'] ?? 'manual',
            referenceId: $data['reference_id'] ?? null,
        ));

        return new JsonResponse(
            ['message' => 'Stock adjusted successfully.'],
            Response::HTTP_OK
        );
    }
}
