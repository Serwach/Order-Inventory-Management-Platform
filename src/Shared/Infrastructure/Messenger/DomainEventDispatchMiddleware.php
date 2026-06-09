<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Shared\Domain\Aggregate\AggregateRoot;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * After a Command is handled, pulls domain events from any AggregateRoot
 * returned by the handler and dispatches them to the event bus.
 */
final class DomainEventDispatchMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly MessageBusInterface $eventBus) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $envelope = $stack->next()->handle($envelope, $stack);

        /** @var HandledStamp|null $stamp */
        $stamp = $envelope->last(HandledStamp::class);

        if ($stamp !== null && $stamp->getResult() instanceof AggregateRoot) {
            foreach ($stamp->getResult()->pullDomainEvents() as $event) {
                $this->eventBus->dispatch($event);
            }
        }

        return $envelope;
    }
}
