<?php

declare(strict_types=1);

namespace App\Catalog\UI\Api;

use App\Catalog\Application\Command\CreateProduct\CreateProductCommand;
use App\Catalog\Application\Query\SearchProducts\SearchProductsQuery;
use App\Catalog\Domain\Entity\Product;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Query\QueryBusInterface;
use App\Shared\Domain\Repository\PaginatedResult;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly CurrentUserProvider $currentUser,
        private readonly int $defaultPaginationLimit,
    ) {}
+
    #[Route('', name: 'products_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var PaginatedResult<array<string, mixed>> $result */
        $result = $this->queryBus->ask(new SearchProductsQuery(
            organizationId: $this->currentUser->getOrganizationId(),
            query: $request->query->get('q'),
            category: $request->query->get('category'),
            active: $request->query->has('active') ? (bool) $request->query->get('active') : null,
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', $this->defaultPaginationLimit),
            sortBy: $request->query->get('sort_by', 'name'),
            sortDir: $request->query->get('sort_dir', 'asc'),
        ));

        return new JsonResponse([
            'data'       => $result->items,
            'pagination' => [
                'total'      => $result->totalCount,
                'page'       => $result->page,
                'limit'      => $result->limit,
                'pages'      => $result->totalPages(),
                'has_next'   => $result->hasNextPage(),
                'has_prev'   => $result->hasPreviousPage(),
            ],
        ]);
    }

    #[Route('', name: 'products_create', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();

        /** @var Product $product */
        $product = null;

        $this->commandBus->dispatch(new CreateProductCommand(
            organizationId: $this->currentUser->getOrganizationId(),
            sku: $data['sku'] ?? '',
            name: $data['name'] ?? '',
            basePriceAmount: (int) ($data['base_price_amount'] ?? 0),
            currency: $data['currency'] ?? 'USD',
            description: $data['description'] ?? null,
            category: $data['category'] ?? null,
            attributes: $data['attributes'] ?? [],
        ));

        return new JsonResponse(
            ['message' => 'Product created successfully.'],
            Response::HTTP_CREATED
        );
    }

    /** @param Product $product */
    private function serializeProduct(Product $product): array
    {
        return [
            'id'          => $product->id()->value(),
            'sku'         => $product->sku()->value(),
            'name'        => $product->name(),
            'description' => $product->description(),
            'base_price'  => [
                'amount'   => $product->basePrice()->amount(),
                'currency' => $product->basePrice()->currency(),
                'formatted' => $product->basePrice()->formatted(),
            ],
            'category'    => $product->category(),
            'attributes'  => $product->attributes(),
            'active'      => $product->isActive(),
            'created_at'  => $product->createdAt()->format(\DateTimeInterface::RFC3339),
            'updated_at'  => $product->updatedAt()->format(\DateTimeInterface::RFC3339),
        ];
    }
}
