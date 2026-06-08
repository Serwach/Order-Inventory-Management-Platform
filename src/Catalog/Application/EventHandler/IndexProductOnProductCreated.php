<?php

declare(strict_types=1);

namespace App\Catalog\Application\EventHandler;

use App\Catalog\Domain\Event\ProductCreated;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\ValueObject\ProductId;
use App\Catalog\Infrastructure\Search\OpenSearchProductIndexer;
use App\Shared\Application\Event\EventHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class IndexProductOnProductCreated implements EventHandlerInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly OpenSearchProductIndexer $indexer,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ProductCreated $event): void
    {
        $product = $this->products->findById(ProductId::fromString($event->productId));

        if ($product === null) {
            $this->logger->warning('Product not found for indexing', ['product_id' => $event->productId]);

            return;
        }

        $this->indexer->ensureIndex($event->organizationId);
        $this->indexer->index($product);

        $this->logger->info('Product indexed', [
            'product_id'      => $event->productId,
            'organization_id' => $event->organizationId,
        ]);
    }
}
