<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(private readonly MessageBusInterface $messageBus) {}

    public function dispatch(CommandInterface $command): void
    {
        try {
            $envelope = $this->messageBus->dispatch($command);
            $handled  = $envelope->last(HandledStamp::class);

            if ($handled === null) {
                throw new \LogicException(
                    sprintf('Command "%s" was not handled.', $command::class)
                );
            }
        } catch (HandlerFailedException $e) {
            $nested = $e->getWrappedExceptions();

            if (count($nested) === 1) {
                throw current($nested);
            }

            throw $e;
        }
    }
}
