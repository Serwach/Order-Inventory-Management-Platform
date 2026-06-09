<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

abstract class DomainEvent
{
    public readonly string $eventId;
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->eventId = Uuid::v7()->toRfc4122();
        $this->occurredAt = new \DateTimeImmutable();
    }

    abstract public function eventName(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id'   => $this->eventId,
            'event_name' => $this->eventName(),
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339_EXTENDED),
            'payload'    => $this->payload(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function payload(): array;
}
