<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Domain\ValueObject\Sku;
use App\Shared\Domain\Repository\PaginatedResult;
use App\Shared\Domain\ValueObject\Pagination;

interface ProductRepositoryInterface
{
    public function findById(ProductId $id): ?Product;

    public function findByOrganizationAndSku(string $organizationId, Sku $sku): ?Product;

    /**
     * @param array<string, mixed> $filters
     * @return PaginatedResult<Product>
     */
    public function search(string $organizationId, array $filters, Pagination $pagination): PaginatedResult;

    public function save(Product $product): void;

    public function nextIdentity(): ProductId;
}
