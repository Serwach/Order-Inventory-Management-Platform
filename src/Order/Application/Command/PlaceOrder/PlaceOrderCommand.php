<?php

declare(strict_types=1);

namespace App\Order\Application\Command\PlaceOrder;

use App\Shared\Application\Command\CommandInterface;

final readonly class PlaceOrderCommand implements CommandInterface
{
    /**
     * @param list<array{productId:string, variantId:string|null, sku:string, name:string, quantity:int, unitPriceAmount:int, currency:string}> $items
     * @param array<string, string> $shippingAddress
     */
    public function __construct(
        public string $organizationId,
        public string $customerId,
        public array $items,
        public array $shippingAddress,
        public string $currency,
        public ?string $notes = null,
    ) {}
}
