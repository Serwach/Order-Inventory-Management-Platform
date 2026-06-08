<?php

declare(strict_types=1);

namespace App\Order\Application\Query\ListOrders;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListOrdersQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public ?string $customerId = null,
        public ?string $status = null,
        public ?string $from = null,
        public ?string $to = null,
        public int $page = 1,
        public int $limit = 25,
        public string $sortBy = 'placedAt',
        public string $sortDir = 'desc',
    ) {}
}
