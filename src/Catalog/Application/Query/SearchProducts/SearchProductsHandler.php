<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query\SearchProducts;

use App\Catalog\Infrastructure\Search\OpenSearchProductIndexer;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Shared\Domain\Repository\PaginatedResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class SearchProductsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OpenSearchProductIndexer $indexer,
    ) {}

    public function __invoke(SearchProductsQuery $query): PaginatedResult
    {
        return $this->indexer->search(
            organizationId: $query->organizationId,
            searchQuery: $query->query,
            filters: array_filter([
                'category' => $query->category,
                'active'   => $query->active,
            ], fn ($v) => $v !== null),
            page: $query->page,
            limit: $query->limit,
        );
    }
}
