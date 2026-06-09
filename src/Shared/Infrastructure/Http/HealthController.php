<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController extends AbstractController
{
    public function __construct(private readonly Connection $connection) {}

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
        ];

        $healthy = !in_array(false, $checks, strict: true);

        return new JsonResponse(
            [
                'status' => $healthy ? 'ok' : 'degraded',
                'checks' => $checks,
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
            ],
            $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE
        );
    }

    private function checkDatabase(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
