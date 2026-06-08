<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Search;

use App\Catalog\Domain\Entity\Product;
use App\Shared\Domain\Repository\PaginatedResult;
use OpenSearch\Client;
use Psr\Log\LoggerInterface;

final class OpenSearchProductIndexer
{
    private const INDEX_ALIAS = 'products';

    public function __construct(
        private readonly Client $client,
        private readonly string $indexPrefix,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Product $product): void
    {
        try {
            $this->client->index([
                'index' => $this->indexName($product->organizationId()),
                'id'    => $product->id()->value(),
                'body'  => $this->toDocument($product),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to index product in OpenSearch', [
                'product_id' => $product->id()->value(),
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function delete(string $organizationId, string $productId): void
    {
        try {
            $this->client->delete([
                'index' => $this->indexName($organizationId),
                'id'    => $productId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to delete product from OpenSearch', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return PaginatedResult<array<string, mixed>>
     */
    public function search(
        string $organizationId,
        ?string $searchQuery,
        array $filters = [],
        int $page = 1,
        int $limit = 25,
    ): PaginatedResult {
        $must   = [['term' => ['organization_id' => $organizationId]]];
        $filter = [];

        if ($searchQuery !== null && $searchQuery !== '') {
            $must[] = [
                'multi_match' => [
                    'query'     => $searchQuery,
                    'fields'    => ['name^3', 'sku^2', 'description', 'category'],
                    'fuzziness' => 'AUTO',
                    'type'      => 'best_fields',
                ],
            ];
        }

        if (isset($filters['category'])) {
            $filter[] = ['term' => ['category' => $filters['category']]];
        }

        if (isset($filters['active'])) {
            $filter[] = ['term' => ['active' => $filters['active']]];
        }

        $body = [
            'query' => [
                'bool' => array_filter([
                    'must'   => $must,
                    'filter' => $filter ?: null,
                ]),
            ],
            'from' => ($page - 1) * $limit,
            'size' => $limit,
        ];

        $response = $this->client->search([
            'index' => $this->indexName($organizationId),
            'body'  => $body,
        ]);

        $total = $response['hits']['total']['value'] ?? 0;
        $items = array_map(
            static fn (array $hit) => $hit['_source'],
            $response['hits']['hits'] ?? []
        );

        return new PaginatedResult(
            items: $items,
            totalCount: $total,
            page: $page,
            limit: $limit,
        );
    }

    public function ensureIndex(string $organizationId): void
    {
        $index = $this->indexName($organizationId);

        if ($this->client->indices()->exists(['index' => $index])) {
            return;
        }

        $this->client->indices()->create([
            'index' => $index,
            'body'  => [
                'settings' => [
                    'number_of_shards'   => 1,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    'properties' => [
                        'id'              => ['type' => 'keyword'],
                        'organization_id' => ['type' => 'keyword'],
                        'sku'             => ['type' => 'keyword'],
                        'name'            => ['type' => 'text', 'analyzer' => 'standard', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'description'     => ['type' => 'text'],
                        'category'        => ['type' => 'keyword'],
                        'base_price'      => ['type' => 'integer'],
                        'currency'        => ['type' => 'keyword'],
                        'attributes'      => ['type' => 'object', 'enabled' => false],
                        'active'          => ['type' => 'boolean'],
                        'created_at'      => ['type' => 'date'],
                        'updated_at'      => ['type' => 'date'],
                    ],
                ],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function toDocument(Product $product): array
    {
        return [
            'id'              => $product->id()->value(),
            'organization_id' => $product->organizationId(),
            'sku'             => $product->sku()->value(),
            'name'            => $product->name(),
            'description'     => $product->description(),
            'category'        => $product->category(),
            'base_price'      => $product->basePrice()->amount(),
            'currency'        => $product->basePrice()->currency(),
            'attributes'      => $product->attributes(),
            'active'          => $product->isActive(),
            'created_at'      => $product->createdAt()->format(\DateTimeInterface::RFC3339),
            'updated_at'      => $product->updatedAt()->format(\DateTimeInterface::RFC3339),
        ];
    }

    private function indexName(string $organizationId): string
    {
        return sprintf('%s_%s_%s', $this->indexPrefix, self::INDEX_ALIAS, $organizationId);
    }
}
