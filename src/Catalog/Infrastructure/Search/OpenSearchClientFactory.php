<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Search;

use OpenSearch\Client;
use OpenSearch\ClientBuilder;

final class OpenSearchClientFactory
{
    public function __construct(private readonly string $dsn) {}

    public function create(): Client
    {
        return ClientBuilder::create()
            ->setHosts([$this->dsn])
            ->build();
    }
}
