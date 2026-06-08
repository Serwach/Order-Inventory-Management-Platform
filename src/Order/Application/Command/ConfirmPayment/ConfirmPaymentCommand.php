<?php

declare(strict_types=1);

namespace App\Order\Application\Command\ConfirmPayment;

use App\Shared\Application\Command\CommandInterface;

final readonly class ConfirmPaymentCommand implements CommandInterface
{
    public function __construct(
        public string $orderId,
        public string $organizationId,
        public string $paymentId,
    ) {}
}
