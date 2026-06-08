<?php

declare(strict_types=1);

namespace App\Order\Domain\Service;

use App\Order\Domain\ValueObject\OrderNumber;

interface OrderNumberGeneratorInterface
{
    public function next(string $organizationId): OrderNumber;
}
