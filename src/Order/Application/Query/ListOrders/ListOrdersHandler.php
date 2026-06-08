<?php

declare(strict_types=1);

namespace App\Order\Application\Query\ListOrders;

use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use App\Shared\Domain\Repository\PaginatedResult;
use App\Shared\Domain\ValueObject\Pagination;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListOrdersHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function __invoke(ListOrdersQuery $query): PaginatedResult
    {
        $pagination = Pagination::fromRequest($query->page, $query->limit);

        $filters = array_filter([
            'customer_id' => $query->customerId,
            'status'      => $query->status,
            'from'        => $query->from,
            'to'          => $query->to,
            'sort_by'     => $query->sortBy,
            'sort_dir'    => $query->sortDir,
        ]);

        return $this->orders->findByOrganization(
            $query->organizationId,
            $filters,
            $pagination,
        );
    }
}
