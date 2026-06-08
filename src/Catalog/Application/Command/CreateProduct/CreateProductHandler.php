<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\CreateProduct;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\ValueObject\Sku;
use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\ValueObject\Money;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateProductHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function __invoke(CreateProductCommand $command): Product
    {
        $sku = Sku::fromString($command->sku);

        if ($this->products->findByOrganizationAndSku($command->organizationId, $sku) !== null) {
            throw ConflictException::alreadyExists('Product', $sku->value());
        }

        $product = Product::create(
            id: $this->products->nextIdentity(),
            organizationId: $command->organizationId,
            sku: $sku,
            name: $command->name,
            basePrice: Money::of($command->basePriceAmount, $command->currency),
            description: $command->description,
            category: $command->category,
            attributes: $command->attributes,
        );

        $this->products->save($product);

        return $product;
    }
}
