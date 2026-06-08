<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrder;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetOrderQuery implements QueryInterface
{
    public function __construct(
        public string $orderId,
        public string $organizationId,
    ) {}
}
