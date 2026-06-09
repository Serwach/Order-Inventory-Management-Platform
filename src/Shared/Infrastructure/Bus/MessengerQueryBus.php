<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Query\QueryBusInterface;
use App\Shared\Application\Query\QueryInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerQueryBus implements QueryBusInterface
{
    public function __construct(private readonly MessageBusInterface $messageBus) {}

    public function ask(QueryInterface $query): mixed
    {
        try {
            $envelope = $this->messageBus->dispatch($query);
            $handled  = $envelope->last(HandledStamp::class);

            if ($handled === null) {
                throw new \LogicException(
                    sprintf('Query "%s" produced no result.', $query::class)
                );
            }

            return $handled->getResult();
        } catch (HandlerFailedException $e) {
            $nested = $e->getWrappedExceptions();

            if (count($nested) === 1) {
                throw current($nested);
            }

            throw $e;
        }
    }
}
