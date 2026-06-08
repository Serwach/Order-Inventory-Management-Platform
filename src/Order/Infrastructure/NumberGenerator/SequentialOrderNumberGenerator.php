<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\NumberGenerator;

use App\Order\Domain\Service\OrderNumberGeneratorInterface;
use App\Order\Domain\ValueObject\OrderNumber;
use Doctrine\DBAL\Connection;

/**
 * Generates sequential order numbers using a PostgreSQL sequence per organization+year.
 * Thread-safe because NEXTVAL is atomic.
 */
final class SequentialOrderNumberGenerator implements OrderNumberGeneratorInterface
{
    public function __construct(private readonly Connection $connection) {}

    public function next(string $organizationId): OrderNumber
    {
        $year         = (int) (new \DateTimeImmutable())->format('Y');
        $sequenceName = $this->sequenceName($organizationId, $year);

        $this->ensureSequenceExists($sequenceName);

        $sequence = (int) $this->connection->fetchOne(
            sprintf("SELECT NEXTVAL('%s')", $sequenceName)
        );

        return OrderNumber::generate($year, $sequence);
    }

    private function ensureSequenceExists(string $sequenceName): void
    {
        $exists = (bool) $this->connection->fetchOne(
            "SELECT EXISTS(SELECT 1 FROM pg_sequences WHERE schemaname = 'public' AND sequencename = ?)",
            [$sequenceName]
        );

        if (!$exists) {
            $this->connection->executeStatement(
                sprintf(
                    "CREATE SEQUENCE IF NOT EXISTS %s START 1 INCREMENT 1 MINVALUE 1 MAXVALUE 999999 CYCLE",
                    $sequenceName
                )
            );
        }
    }

    private function sequenceName(string $organizationId, int $year): string
    {
        // Use first 8 chars of org ID to keep name short but unique
        return sprintf('order_seq_%s_%d', substr(str_replace('-', '', $organizationId), 0, 8), $year);
    }
}
