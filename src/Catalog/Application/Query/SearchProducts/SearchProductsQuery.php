<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query\SearchProducts;

use App\Shared\Application\Query\QueryInterface;

final readonly class SearchProductsQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public ?string $query = null,
        public ?string $category = null,
        public ?bool $active = null,
        public int $page = 1,
        public int $limit = 25,
        public string $sortBy = 'name',
        public string $sortDir = 'asc',
    ) {}
}
