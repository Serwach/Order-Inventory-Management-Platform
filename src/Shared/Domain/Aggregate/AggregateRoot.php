<?php

declare(strict_types=1);

namespace App\Shared\Domain\Aggregate;

use App\Shared\Domain\Event\DomainEvent;

abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    final protected function raise(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Pull and clear all pending domain events.
     *
     * @return list<DomainEvent>
     */
    final public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    final public function hasPendingEvents(): bool
    {
        return $this->domainEvents !== [];
    }
}
